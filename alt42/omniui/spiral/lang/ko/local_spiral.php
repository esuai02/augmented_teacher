<?php
/**
 * Korean language strings for Spiral Scheduler
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = '스파이럴 스케줄러';
$string['generate'] = '자동 편성';
$string['publish'] = '발행';
$string['ratio_preview'] = '선행 비율';
$string['ratio_review'] = '복습 비율';
$string['conflict'] = '충돌';
$string['schedule'] = '스케줄';
$string['editor'] = '편집기';
$string['dashboard'] = '대시보드';
$string['error_permission'] = '권한이 없습니다.';
$string['error_notfound'] = '요청한 항목을 찾을 수 없습니다.';
$string['error_invalid'] = '잘못된 요청입니다.';
$string['success_generated'] = '스케줄이 성공적으로 생성되었습니다.';
$string['success_published'] = '스케줄이 성공적으로 발행되었습니다.';
$string['success_modified'] = '변경사항이 저장되었습니다.';
$string['confirm_publish'] = '스케줄을 발행하시겠습니까? 학생에게 알림이 전송됩니다.';
$string['student_select'] = '학생 선택';
$string['start_date'] = '시작일';
$string['end_date'] = '종료일 (시험일)';
$string['hours_per_week'] = '주당 학습 시간';
$string['preview_learning'] = '선행학습';
$string['review_learning'] = '복습';
$string['time_overlap'] = '시간 중복';
$string['prerequisite'] = '선수학습 필요';
$string['cognitive_load'] = '인지 부하 초과';
$string['physical_limit'] = '일일 한도 초과';
$string['resolve'] = '해결';
$string['save_changes'] = '변경사항 저장';
$string['reset'] = '초기화';
$string['week'] = '주차';
$string['day_monday'] = '월요일';
$string['day_tuesday'] = '화요일';
$string['day_wednesday'] = '수요일';
$string['day_thursday'] = '목요일';
$string['day_friday'] = '금요일';
$string['day_saturday'] = '토요일';
$string['day_sunday'] = '일요일';
$string['minutes'] = '분';
$string['hours'] = '시간';
$string['difficulty'] = '난이도';
$string['unit'] = '단원';
$string['session'] = '세션';
$string['total'] = '총';
$string['loading'] = '로딩 중...';
$string['no_students'] = '담당 학생이 없습니다.';
$string['no_schedule'] = '생성된 스케줄이 없습니다.';
$string['drag_to_move'] = '드래그하여 이동';
$string['drop_here'] = '여기에 놓기';

// Conflict severities
$string['conflict_severity_low'] = '낮음';
$string['conflict_severity_medium'] = '보통';
$string['conflict_severity_high'] = '높음';
$string['conflict_severity_critical'] = '심각';

// Task and KPI strings
$string['task_recompute_plans'] = '스케줄 재평가 및 KPI 수집';
$string['kpi_dashboard_title'] = '스파이럴 스케줄러 KPI 대시보드';
$string['kpi_ratio'] = '7:3 준수율';
$string['kpi_ratio_desc'] = 'Preview/Review 세션의 이상적 비율 달성도';
$string['kpi_conflict'] = '충돌 발생률';
$string['kpi_conflict_desc'] = '스케줄링 충돌이 발생한 비율';
$string['kpi_completion'] = '완료율';
$string['kpi_completion_desc'] = '예정된 학습 세션의 완료 비율';
$string['kpi_modcnt'] = '교사 수정횟수';
$string['kpi_modcnt_desc'] = '교사가 자동 생성 스케줄을 수정한 횟수';
$string['kpi_satisfaction'] = '사용자 만족도';
$string['kpi_satisfaction_desc'] = '시스템 활용도 기반 간접 만족도 지표';
$string['kpi_utilization'] = '시스템 활용률';
$string['kpi_utilization_desc'] = '전체 교사 대비 시스템 사용률';

// KPI status messages
$string['kpi_summary_excellent'] = '모든 지표가 우수한 상태입니다.';
$string['kpi_summary_good'] = '대부분의 지표가 양호한 상태입니다.';
$string['kpi_summary_needs_attention'] = '일부 지표에 주의가 필요합니다.';
$string['kpi_alert_threshold'] = '임계치를 벗어났습니다.';

// Trend indicators
$string['trend_up'] = '증가 추세';
$string['trend_down'] = '감소 추세';
$string['trend_stable'] = '안정';

// Actions
$string['last_updated'] = '마지막 업데이트';
$string['quick_actions'] = '빠른 작업';
$string['refresh_kpis'] = 'KPI 새로고침';
$string['view_trends'] = '추세 보기';
$string['resolve_alerts'] = '알림 해결';

// Legacy links
$string['legacy_links'] = '기존 시스템 바로가기';
$string['view_student_schedule'] = '학생 개인 일정 보기';
$string['view_attendance_records'] = '출석 기록 보기';
$string['view_dashboard'] = '대시보드 보기';