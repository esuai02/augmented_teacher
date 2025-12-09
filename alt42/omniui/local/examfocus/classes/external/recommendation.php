<?php
/**
 * ExamFocus External Web Services for Recommendations
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examfocus\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/examfocus/classes/service/recommendation_engine.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use local_examfocus\service\recommendation_engine;

/**
 * 추천 관련 외부 웹서비스
 */
class recommendation extends external_api {
    
    /**
     * 추천 조회 매개변수 정의
     */
    public static function get_recommendation_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'options' => new external_single_structure([
                'include_stats' => new external_value(PARAM_BOOL, 'Include user statistics', VALUE_DEFAULT, false),
                'force_refresh' => new external_value(PARAM_BOOL, 'Force refresh cache', VALUE_DEFAULT, false)
            ], 'Additional options', VALUE_DEFAULT, [])
        ]);
    }
    
    /**
     * 사용자 추천 조회
     */
    public static function get_recommendation($userid, $options = []) {
        global $USER, $DB;
        
        // 매개변수 검증
        $params = self::validate_parameters(self::get_recommendation_parameters(), [
            'userid' => $userid,
            'options' => $options
        ]);
        
        $userid = $params['userid'];
        $options = $params['options'];
        
        // 권한 체크
        $context = \context_system::instance();
        self::validate_context($context);
        
        // 자신의 정보이거나 관리자인지 확인
        if ($userid != $USER->id && !has_capability('local/examfocus:view_all_recommendations', $context)) {
            throw new moodle_exception('nopermissions', 'error');
        }
        
        try {
            $engine = new recommendation_engine();
            $recommendation = $engine->recommend_for_user($userid);
            
            // 사용자 통계 포함 여부
            if ($options['include_stats']) {
                $recommendation['user_stats'] = self::get_user_stats($userid);
            }
            
            return $recommendation;
            
        } catch (\Exception $e) {
            throw new moodle_exception('error_getting_recommendation', 'local_examfocus', '', $e->getMessage());
        }
    }
    
    /**
     * 추천 조회 반환값 정의
     */
    public static function get_recommendation_returns() {
        return new external_single_structure([
            'has_recommendation' => new external_value(PARAM_BOOL, 'Whether has active recommendation'),
            'mode' => new external_value(PARAM_TEXT, 'Recommended study mode', VALUE_OPTIONAL),
            'title' => new external_value(PARAM_TEXT, 'Recommendation title'),
            'message' => new external_value(PARAM_TEXT, 'Recommendation message'),
            'priority' => new external_value(PARAM_TEXT, 'Priority level (urgent/normal/info)'),
            'exam_info' => new external_single_structure([
                'exam_date' => new external_value(PARAM_INT, 'Exam date timestamp', VALUE_OPTIONAL),
                'days_until' => new external_value(PARAM_INT, 'Days until exam', VALUE_OPTIONAL),
                'type' => new external_value(PARAM_TEXT, 'Exam type', VALUE_OPTIONAL),
                'scope' => new external_value(PARAM_TEXT, 'Exam scope', VALUE_OPTIONAL)
            ], 'Exam information', VALUE_OPTIONAL),
            'actions' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Recommended action'),
                'List of recommended actions'
            ),
            'meets_requirements' => new external_value(PARAM_BOOL, 'Whether user meets learning requirements'),
            'auto_switch' => new external_value(PARAM_BOOL, 'Whether auto switch is enabled'),
            'user_stats' => new external_single_structure([
                'total_time' => new external_value(PARAM_FLOAT, 'Total learning time in hours', VALUE_OPTIONAL),
                'week_time' => new external_value(PARAM_FLOAT, 'Weekly learning time in hours', VALUE_OPTIONAL), 
                'recent_activity' => new external_value(PARAM_INT, 'Recent activity count', VALUE_OPTIONAL)
            ], 'User learning statistics', VALUE_OPTIONAL)
        ]);
    }
    
    /**
     * 추천 수락 매개변수 정의
     */
    public static function accept_recommendation_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'mode' => new external_value(PARAM_TEXT, 'Study mode to accept', VALUE_REQUIRED),
            'exam_info' => new external_single_structure([
                'exam_date' => new external_value(PARAM_INT, 'Exam date', VALUE_OPTIONAL),
                'days_until' => new external_value(PARAM_INT, 'Days until exam', VALUE_OPTIONAL)
            ], 'Exam information', VALUE_DEFAULT, [])
        ]);
    }
    
    /**
     * 추천 수락 처리
     */
    public static function accept_recommendation($userid, $mode, $exam_info = []) {
        global $USER;
        
        $params = self::validate_parameters(self::accept_recommendation_parameters(), [
            'userid' => $userid,
            'mode' => $mode,
            'exam_info' => $exam_info
        ]);
        
        // 권한 체크
        $context = \context_system::instance();
        self::validate_context($context);
        
        if ($userid != $USER->id && !has_capability('local/examfocus:manage_recommendations', $context)) {
            throw new moodle_exception('nopermissions', 'error');
        }
        
        try {
            $engine = new recommendation_engine();
            $success = $engine->log_recommendation_accepted($userid, $mode, $exam_info);
            
            if ($success) {
                // 성공 시 실제 모드 전환 수행 (selectmode.php 연동 포인트)
                self::switch_study_mode($userid, $mode);
            }
            
            return [
                'success' => $success,
                'message' => $success ? 
                    get_string('recommendation_accepted', 'local_examfocus') :
                    get_string('error_accepting_recommendation', 'local_examfocus'),
                'redirect_url' => self::get_mode_url($mode, $userid)
            ];
            
        } catch (\Exception $e) {
            throw new moodle_exception('error_accepting_recommendation', 'local_examfocus', '', $e->getMessage());
        }
    }
    
    /**
     * 추천 수락 반환값 정의
     */
    public static function accept_recommendation_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether acceptance was successful'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message'),
            'redirect_url' => new external_value(PARAM_URL, 'URL to redirect to', VALUE_OPTIONAL)
        ]);
    }
    
    /**
     * 추천 거부 매개변수 정의
     */
    public static function dismiss_recommendation_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'mode' => new external_value(PARAM_TEXT, 'Study mode to dismiss', VALUE_REQUIRED)
        ]);
    }
    
    /**
     * 추천 거부 처리
     */
    public static function dismiss_recommendation($userid, $mode) {
        global $USER;
        
        $params = self::validate_parameters(self::dismiss_recommendation_parameters(), [
            'userid' => $userid,
            'mode' => $mode
        ]);
        
        // 권한 체크
        $context = \context_system::instance();
        self::validate_context($context);
        
        if ($userid != $USER->id) {
            throw new moodle_exception('nopermissions', 'error');
        }
        
        try {
            $engine = new recommendation_engine();
            $success = $engine->log_recommendation_dismissed($userid, $mode);
            
            return [
                'success' => $success,
                'message' => $success ? 
                    get_string('recommendation_dismissed', 'local_examfocus') :
                    get_string('error_dismissing_recommendation', 'local_examfocus')
            ];
            
        } catch (\Exception $e) {
            throw new moodle_exception('error_dismissing_recommendation', 'local_examfocus', '', $e->getMessage());
        }
    }
    
    /**
     * 추천 거부 반환값 정의
     */
    public static function dismiss_recommendation_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether dismissal was successful'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message')
        ]);
    }
    
    /**
     * 사용자 통계 조회 (내부 사용)
     */
    private static function get_user_stats($userid) {
        global $DB;
        
        $stats = [
            'total_time' => 0,
            'week_time' => 0,
            'recent_activity' => 0
        ];
        
        try {
            // 총 학습 시간
            $total_record = $DB->get_record('block_use_stats_totaltime', ['userid' => $userid], 'totaltime');
            if ($total_record) {
                $stats['total_time'] = round($total_record->totaltime / 3600, 1);
            }
            
            // 주간 활동
            $week_ago = time() - (7 * 24 * 60 * 60);
            $recent_count = $DB->count_records_select('abessi_missionlog', 
                'userid = ? AND timecreated > ?', [$userid, $week_ago]);
            $stats['recent_activity'] = $recent_count;
            
        } catch (\Exception $e) {
            // 통계 조회 실패는 빈 값으로 처리
        }
        
        return $stats;
    }
    
    /**
     * 학습 모드 전환 (selectmode.php 연동 포인트)
     */
    private static function switch_study_mode($userid, $mode) {
        // 이곳에서 실제 selectmode.php와 연동하여 모드 전환
        // 현재는 로그만 기록
        global $DB;
        
        try {
            $DB->insert_record('abessi_missionlog', [
                'userid' => $userid,
                'page' => 'examfocus_mode_switched_' . $mode,
                'timecreated' => time()
            ]);
        } catch (\Exception $e) {
            // 로그 실패는 무시
        }
    }
    
    /**
     * 모드별 URL 생성
     */
    private static function get_mode_url($mode, $userid) {
        $base_url = '/local/examfocus/';
        
        $urls = [
            'concept_summary' => $base_url . 'concept_summary.php',
            'review_errors' => $base_url . 'review_errors.php',
            'practice' => $base_url . 'practice.php',
            'exam_day' => $base_url . 'exam_day.php',
            'study' => $base_url . 'study.php'
        ];
        
        $url = $urls[$mode] ?? $base_url . 'quickstart.php';
        return $url . '?user_id=' . $userid;
    }
}