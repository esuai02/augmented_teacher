<?php
/**
 * 한국어 언어 파일
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = '시험 대비 자동 학습 모드';

// 설정
$string['d30_threshold'] = 'D-30 임계값';
$string['d30_threshold_desc'] = '시험 30일 전 알림을 시작할 일수';
$string['d7_threshold'] = 'D-7 임계값';
$string['d7_threshold_desc'] = '시험 7일 전 집중 모드를 시작할 일수';
$string['message_d30'] = 'D-30 메시지';
$string['message_d30_desc'] = '시험 30일 전에 표시할 메시지';
$string['message_d7'] = 'D-7 메시지';
$string['message_d7_desc'] = '시험 7일 전에 표시할 메시지';
$string['min_week_hours'] = '최소 주간 학습시간';
$string['min_week_hours_desc'] = '추천을 위한 최소 주간 학습시간 (시간 단위)';
$string['min_total_hours'] = '최소 누적 학습시간';
$string['min_total_hours_desc'] = '추천을 위한 최소 누적 학습시간 (시간 단위)';
$string['cooldown_hours'] = '알림 쿨다운';
$string['cooldown_hours_desc'] = '알림 무시 후 재표시까지 대기 시간 (시간 단위)';
$string['auto_switch'] = '자동 모드 전환';
$string['auto_switch_desc'] = '추천 수락 시 자동으로 학습 모드 전환';

// 학습 모드
$string['mode_d30'] = 'D-30 추천 모드';
$string['mode_d30_desc'] = '시험 30일 전 추천할 학습 모드';
$string['mode_d7'] = 'D-7 추천 모드';
$string['mode_d7_desc'] = '시험 7일 전 추천할 학습 모드';
$string['mode_review_errors'] = '오답 회독';
$string['mode_concept_summary'] = '개념 요약';
$string['mode_practice_problems'] = '문제 풀이';
$string['mode_key_problems'] = '대표 유형';
$string['mode_final_review'] = '최종 점검';

// UI 텍스트
$string['recommendation_title'] = '📚 시험 대비 학습 모드 추천';
$string['apply_recommendation'] = '추천 모드로 전환';
$string['dismiss'] = '나중에';
$string['exam_approaching'] = '시험이 다가오고 있습니다!';
$string['days_remaining'] = '남은 일수: {$a}일';

// 태스크
$string['task_scan_exams'] = '시험 일정 스캔';
$string['task_send_reminders'] = '학습 알림 전송';

// 권한
$string['examfocus:view_recommendations'] = '학습 추천 보기';
$string['examfocus:manage_settings'] = '설정 관리';
$string['examfocus:manage_rules'] = '규칙 관리';
$string['examfocus:view_statistics'] = '통계 보기';

// 메시지
$string['recommendation_accepted'] = '학습 모드가 변경되었습니다.';
$string['recommendation_dismissed'] = '추천을 무시했습니다. 나중에 다시 알려드리겠습니다.';
$string['no_exam_scheduled'] = '예정된 시험이 없습니다.';
$string['feature_disabled'] = '시험 대비 모드가 비활성화되어 있습니다.';

// 웹서비스 관련
$string['error_accepting_recommendation'] = '추천 적용 중 오류가 발생했습니다.';
$string['error_dismissing_recommendation'] = '추천 무시 중 오류가 발생했습니다.';
$string['error_getting_recommendation'] = '추천 조회 중 오류가 발생했습니다.';

// 시험 관련
$string['exam_detected'] = '시험이 감지되었습니다.';
$string['no_exam_found'] = '예정된 시험을 찾을 수 없습니다.';
$string['exam_date_format'] = 'Y년 m월 d일';

// 모드 선택 페이지
$string['select_mode'] = '학습 모드 선택';
$string['study_mode_selection'] = '학습 모드 선택';
$string['mode_concept_summary_title'] = '개념요약 모드';
$string['mode_review_errors_title'] = '오답 회독 모드';
$string['mode_practice_title'] = '실전 연습 모드';
$string['mode_exam_day_title'] = '시험 당일 모드';
$string['mode_study_title'] = '일반 학습 모드';
$string['mode_custom_title'] = '맞춤형 모드';