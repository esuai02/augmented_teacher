<?php
/**
 * Onboarding Report Generator
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_generator.php
 * Location: Line 1
 */

require_once 'report_service.php';

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * Generate HTML report from onboarding data
 * @param array $data Combined onboarding data
 * @return string HTML report content
 */
function generateReportHTML($data) {
    if (!$data['success']) {
        return '<div class="error">데이터 로딩 실패: ' . htmlspecialchars($data['error'] ?? 'Unknown error') . '</div>';
    }

    $info = $data['info'];
    $assessment = $data['assessment'];

    $html = '<div class="onboarding-report">';

    // Header
    $html .= '<div class="report-header">';
    $html .= '<h2>온보딩 리포트</h2>';
    $html .= '<p class="generated-time">생성 시각: ' . date('Y-m-d H:i:s', $data['timestamp']) . '</p>';
    $html .= '</div>';

    // Basic Info Section
    $html .= '<div class="report-section">';
    $html .= '<h3>기본 정보</h3>';
    $html .= '<table class="info-table">';
    $html .= '<tr><td><strong>이름:</strong></td><td>' . htmlspecialchars($info['studentName'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>이메일:</strong></td><td>' . htmlspecialchars($info['email'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>전화:</strong></td><td>' . htmlspecialchars($info['phone'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>지역:</strong></td><td>' . htmlspecialchars($info['city'] ?? 'N/A') . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    // Student Profile Section
    if (!empty($info['learning_style']) || !empty($info['mbti_type'])) {
        $html .= '<div class="report-section">';
        $html .= '<h3>학습 프로필</h3>';
        $html .= '<table class="info-table">';

        if (!empty($info['learning_style'])) {
            $html .= '<tr><td><strong>학습 스타일:</strong></td><td>' . htmlspecialchars($info['learning_style']) . '</td></tr>';
        }

        if (!empty($info['mbti_type'])) {
            $mbtiDisplay = htmlspecialchars($info['mbti_type']);
            if (!empty($info['mbti_timecreated'])) {
                $mbtiDisplay .= ' <small style="color: #6b7280;">(' . date('Y-m-d H:i', $info['mbti_timecreated']) . ')</small>';
            }
            $html .= '<tr><td><strong>MBTI:</strong></td><td>' . $mbtiDisplay . '</td></tr>';
        }

        if (!empty($info['preferred_motivator'])) {
            $html .= '<tr><td><strong>동기 유형:</strong></td><td>' . htmlspecialchars($info['preferred_motivator']) . '</td></tr>';
        }

        if (!empty($info['daily_active_time'])) {
            $html .= '<tr><td><strong>활동 시간대:</strong></td><td>' . htmlspecialchars($info['daily_active_time']) . '</td></tr>';
        }

        if (isset($info['streak_days']) && $info['streak_days'] > 0) {
            $html .= '<tr><td><strong>연속 학습:</strong></td><td>' . htmlspecialchars($info['streak_days']) . '일</td></tr>';
        }

        if (isset($info['total_interactions']) && $info['total_interactions'] > 0) {
            $html .= '<tr><td><strong>총 상호작용:</strong></td><td>' . htmlspecialchars($info['total_interactions']) . '회</td></tr>';
        }

        $html .= '</table>';
        $html .= '</div>';
    }

    // Assessment Section
    if (!empty($assessment) && isset($assessment['id'])) {
        $html .= '<div class="report-section">';
        $html .= '<h3>학습 스타일 평가</h3>';

        $html .= '<div class="score-grid">';
        $html .= '<div class="score-card">';
        $html .= '<h4>인지적 요소</h4>';
        $html .= '<div class="score">' . round($assessment['cognitive_score'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';

        $html .= '<div class="score-card">';
        $html .= '<h4>감정적 요소</h4>';
        $html .= '<div class="score">' . round($assessment['emotional_score'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';

        $html .= '<div class="score-card">';
        $html .= '<h4>행동적 요소</h4>';
        $html .= '<div class="score">' . round($assessment['behavioral_score'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';

        $html .= '<div class="score-card total">';
        $html .= '<h4>종합 점수</h4>';
        $html .= '<div class="score">' . round($assessment['overall_total'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
    } else {
        $html .= '<div class="report-section">';
        $html .= '<p class="no-data">학습 스타일 평가 데이터가 없습니다.</p>';
        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Save generated report to database
 * @param int $userid User ID
 * @param array $data Source data
 * @param string $reportHTML Generated HTML
 * @param string $reportType Type: 'initial' or 'regenerated'
 * @return array Result with report ID
 */
function saveReport($userid, $data, $reportHTML, $reportType = 'initial') {
    global $DB;

    try {
        $record = new stdClass();
        $record->userid = $userid;
        $record->report_type = $reportType;
        $record->info_data = json_encode($data['info']);
        $record->assessment_id = $data['assessment']['id'] ?? null;
        $record->report_content = $reportHTML;
        $record->generated_at = time();
        $record->generated_by = 'agent01_onboarding';
        $record->status = 'published';
        $record->metadata = json_encode([
            'cognitive_score' => $data['assessment']['cognitive_score'] ?? 0,
            'emotional_score' => $data['assessment']['emotional_score'] ?? 0,
            'behavioral_score' => $data['assessment']['behavioral_score'] ?? 0,
            'overall_total' => $data['assessment']['overall_total'] ?? 0
        ]);

        $reportId = $DB->insert_record('alt42o_onboarding_reports', $record);

        return [
            'success' => true,
            'reportId' => $reportId,
            'message' => 'Report saved successfully'
        ];

    } catch (Exception $e) {
        error_log("saveReport error: " . $e->getMessage() .
                  " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : 0;

    if ($userid <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }

    switch ($_POST['action']) {
        case 'generateReport':
            // Get data
            $data = getOnboardingData($userid);

            if (!$data['success']) {
                echo json_encode($data);
                exit;
            }

            // Generate HTML
            $reportHTML = generateReportHTML($data);

            // Check if regenerating
            $existing = getExistingReport($userid);
            $reportType = ($existing['exists']) ? 'regenerated' : 'initial';

            // Archive old report if exists
            if ($existing['exists'] && isset($existing['report']->id)) {
                $DB->set_field('alt42o_onboarding_reports', 'status', 'archived',
                              ['id' => $existing['report']->id]);
            }

            // Save new report
            $result = saveReport($userid, $data, $reportHTML, $reportType);

            if ($result['success']) {
                $result['reportHTML'] = $reportHTML;
                $result['reportType'] = $reportType;
            }

            echo json_encode($result);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action',
                'file' => __FILE__,
                'line' => __LINE__
            ]);
    }
    exit;
}
