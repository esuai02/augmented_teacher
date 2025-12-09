<?php
/**
 * ExamFocus 플러그인 설정
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_examfocus', get_string('pluginname', 'local_examfocus'));
    
    // D-30 임계값 설정
    $settings->add(new admin_setting_configtext(
        'local_examfocus/d30_threshold',
        get_string('d30_threshold', 'local_examfocus'),
        get_string('d30_threshold_desc', 'local_examfocus'),
        30,
        PARAM_INT
    ));
    
    // D-7 임계값 설정
    $settings->add(new admin_setting_configtext(
        'local_examfocus/d7_threshold',
        get_string('d7_threshold', 'local_examfocus'),
        get_string('d7_threshold_desc', 'local_examfocus'),
        7,
        PARAM_INT
    ));
    
    // D-30 메시지 템플릿
    $settings->add(new admin_setting_configtextarea(
        'local_examfocus/message_d30',
        get_string('message_d30', 'local_examfocus'),
        get_string('message_d30_desc', 'local_examfocus'),
        '시험까지 D-30! 오답 회독 모드를 시작하세요. 체계적인 복습으로 실수를 줄이세요.',
        PARAM_TEXT
    ));
    
    // D-7 메시지 템플릿
    $settings->add(new admin_setting_configtextarea(
        'local_examfocus/message_d7',
        get_string('message_d7', 'local_examfocus'),
        get_string('message_d7_desc', 'local_examfocus'),
        '시험 D-7! 개념요약과 대표유형에 집중하세요. 최종 점검 시간입니다.',
        PARAM_TEXT
    ));
    
    // 최소 주간 학습시간 (시간 단위)
    $settings->add(new admin_setting_configtext(
        'local_examfocus/min_week_hours',
        get_string('min_week_hours', 'local_examfocus'),
        get_string('min_week_hours_desc', 'local_examfocus'),
        5,
        PARAM_INT
    ));
    
    // 최소 누적 학습시간 (시간 단위)
    $settings->add(new admin_setting_configtext(
        'local_examfocus/min_total_hours',
        get_string('min_total_hours', 'local_examfocus'),
        get_string('min_total_hours_desc', 'local_examfocus'),
        50,
        PARAM_INT
    ));
    
    // 알림 쿨다운 (시간 단위)
    $settings->add(new admin_setting_configtext(
        'local_examfocus/cooldown_hours',
        get_string('cooldown_hours', 'local_examfocus'),
        get_string('cooldown_hours_desc', 'local_examfocus'),
        24,
        PARAM_INT
    ));
    
    // 자동 전환 활성화
    $settings->add(new admin_setting_configcheckbox(
        'local_examfocus/auto_switch',
        get_string('auto_switch', 'local_examfocus'),
        get_string('auto_switch_desc', 'local_examfocus'),
        1
    ));
    
    // 추천 모드 (D-30)
    $choices_d30 = [
        'review_errors' => get_string('mode_review_errors', 'local_examfocus'),
        'concept_summary' => get_string('mode_concept_summary', 'local_examfocus'),
        'practice_problems' => get_string('mode_practice_problems', 'local_examfocus')
    ];
    $settings->add(new admin_setting_configselect(
        'local_examfocus/mode_d30',
        get_string('mode_d30', 'local_examfocus'),
        get_string('mode_d30_desc', 'local_examfocus'),
        'review_errors',
        $choices_d30
    ));
    
    // 추천 모드 (D-7)
    $choices_d7 = [
        'concept_summary' => get_string('mode_concept_summary', 'local_examfocus'),
        'key_problems' => get_string('mode_key_problems', 'local_examfocus'),
        'final_review' => get_string('mode_final_review', 'local_examfocus')
    ];
    $settings->add(new admin_setting_configselect(
        'local_examfocus/mode_d7',
        get_string('mode_d7', 'local_examfocus'),
        get_string('mode_d7_desc', 'local_examfocus'),
        'concept_summary',
        $choices_d7
    ));
    
    $ADMIN->add('localplugins', $settings);
}