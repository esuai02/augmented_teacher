<?php
/**
 * 콘텐츠 검수 시스템
 * 교육 콘텐츠의 질을 체계적으로 평가하고 관리
 *
 * @package    local_augmented_teacher
 * @copyright  2025 KAIST TOUCH MATH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 파라미터 수신
$studentid = $_GET["userid"];
$cntid = $_GET["cntid"];
$notetitle = $_GET["title"];
$timecreated = time();

// 사용자 정보 조회
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid'");
$stdname = $thisuser->firstname . $thisuser->lastname;

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->role;

// 콘텐츠 페이지 목록 조회
$cntpages = $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cntid' ORDER BY pagenum ASC");
$result = json_decode(json_encode($cntpages), True);

// 콘텐츠 데이터 구성
$contents = array();
unset($value);
foreach($result as $value) {
    $title = $value['title'];
    $npage = $value['pagenum'];
    $contentsid = $value['id'];
    $wboardid = 'keytopic_review_' . $contentsid . '_user' . $studentid;

    // 이미지 추출
    $ctext = $value['pageicontent'];
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctext);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $imgSrc = '';

    foreach($imageTags as $imageTag) {
        $imgSrc = $imageTag->getAttribute('src');
        $imgSrc = str_replace(' ', '%20', $imgSrc);
        if(strpos($imgSrc, 'MATRIX') !== false || strpos($imgSrc, 'MATH') !== false || strpos($imgSrc, 'imgur') !== false) {
            break;
        }
    }

    // mdl_abessi_messages 테이블에서 url 조회
    $noteUrl = '';
    try {
        $messageRecord = $DB->get_record_sql(
            "SELECT url FROM mdl_abessi_messages
             WHERE contentsid = ? AND userid = ?
             ORDER BY timecreated DESC
             LIMIT 1",
            [$contentsid, $studentid]
        );

        if ($messageRecord && !empty($messageRecord->url)) {
            $noteUrl = $messageRecord->url;
        }
    } catch (Exception $e) {
        error_log("Note URL load error for contentsid {$contentsid}: " . $e->getMessage());
    }

    // 콘텐츠 배열에 추가
    $contents[] = array(
        'id' => 'MC' . str_pad($npage, 3, '0', STR_PAD_LEFT),
        'title' => $title,
        'topic' => '수학',
        'difficulty' => '중',
        'pagenum' => $npage,
        'contentsid' => $contentsid,
        'wboardid' => $wboardid,
        'noteUrl' => $noteUrl,
        'status' => 'pending',
        'currentLevel' => null,
        'teachers' => 'KTM 교사진',
        'imgSrc' => $imgSrc,
        'audiourl' => $value['audiourl'] ?? '',
        'audiourl2' => $value['audiourl2'] ?? '',
        'reflections1' => $value['reflections1'] ?? ''
    );
}

// 기존 검수 데이터 로드 (DB에서) - 여러 검수자 지원
$existingReviews = array();
foreach($contents as $content) {
    try {
        // 모든 최신 검수 가져오기 (reviewer별로 하나씩)
        $reviews = $DB->get_records_sql(
            "SELECT id, review_level, review_status, timecreated, reviewer_name, reviewer_id
             FROM mdl_abessi_content_reviews
             WHERE contentsid = ? AND is_latest = 1
             ORDER BY timecreated DESC",
            [$content['contentsid']]
        );

        if ($reviews) {
            $reviewersArray = array();
            $latestStatus = 'pending';
            $latestLevel = null;

            foreach ($reviews as $review) {
                $reviewersArray[] = array(
                    'id' => $review->reviewer_id,
                    'name' => $review->reviewer_name,
                    'level' => $review->review_level,
                    'status' => $review->review_status,
                    'date' => date('Y-m-d', $review->timecreated)
                );

                // 가장 최근 검수의 상태/레벨 사용
                if (!$latestLevel) {
                    $latestLevel = $review->review_level;
                    $latestStatus = $review->review_status;
                }
            }

            $existingReviews[$content['contentsid']] = array(
                'reviewers' => $reviewersArray,
                'status' => $latestStatus,
                'level' => $latestLevel
            );
        }
    } catch (Exception $e) {
        // 오류 발생 시 로그만 남기고 계속 진행
        error_log("Review load error for contentsid {$content['contentsid']}: " . $e->getMessage());
    }
}

// 검수 데이터를 contents 배열에 병합
foreach($contents as &$content) {
    if (isset($existingReviews[$content['contentsid']])) {
        $content['reviewers'] = $existingReviews[$content['contentsid']]['reviewers'];
        $content['status'] = $existingReviews[$content['contentsid']]['status'];
        $content['currentLevel'] = $existingReviews[$content['contentsid']]['level'];
    } else {
        $content['reviewers'] = array(); // 빈 배열로 초기화
    }
}
unset($content); // Destroy reference to avoid bugs

// JSON 데이터로 변환
$contentsJson = json_encode($contents, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 콘텐츠 검수 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background-color: #f9fafb;
            color: #111827;
            line-height: 1.6;
        }

        .container {
            min-height: 100vh;
            padding: 24px;
        }

        .max-width {
            max-width: 90%;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header p {
            color: #6b7280;
            font-size: 14px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 0.5fr 1.3fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .content-list, .review-form, .creation-tools {
            background-color: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .content-list {
            padding: 20px;
            height: fit-content;
            max-height: 80vh;
            overflow-y: auto;
        }

        .list-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .content-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .content-item {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .content-item:hover {
            border-color: #3b82f6;
        }

        .content-item.selected {
            border: 2px solid #3b82f6;
            background-color: #eff6ff;
        }

        .content-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            transition: color 0.2s ease;
        }

        .content-title:hover {
            color: #3b82f6;
        }

        .content-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .content-teachers {
            font-size: 11px;
            color: #7c3aed;
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }

        /* 검수자 Badge 스타일 (레벨별 색상) */
        .reviewer-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            color: white;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .reviewer-badge-L1 {
            background-color: #ef4444; /* 빨강 - 심각한 결함 */
        }

        .reviewer-badge-L2 {
            background-color: #f97316; /* 주황 - 학습효과 우려 */
        }

        .reviewer-badge-L3 {
            background-color: #eab308; /* 노랑 - 표준적 */
        }

        .reviewer-badge-L4 {
            background-color: #22c55e; /* 초록 - 잘 처리됨 */
        }

        .reviewer-badge-L5 {
            background-color: #3b82f6; /* 파랑 - 완성도 높음 */
        }

        .content-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .status-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background-color: #dcfce7;
            color: #15803d;
        }

        .level-badge {
            margin-top: 8px;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .review-form {
            padding: 24px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .review-empty {
            text-align: center;
            color: #6b7280;
            padding: 40px 24px;
        }

        .review-empty svg {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            opacity: 0.3;
        }

        .form-section {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .content-image-container {
            max-height: 100px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: max-height 0.3s ease;
            cursor: pointer;
        }

        .content-image-container:hover {
            max-height: 2000px;
        }

        .content-image-container img {
            width: 100%;
            display: block;
        }

        .form-label.required::after {
            content: ' *';
            color: #ef4444;
        }

        .form-meta {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .form-meta-teachers {
            color: #7c3aed;
            font-weight: 500;
        }

        .level-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .level-button {
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid;
            background-color: white;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .level-button:hover {
            transform: translateY(-2px);
        }

        .level-button.selected {
            font-size: 13px;
            padding: 6px 8px;
        }

        .level-text {
            font-size: 12px;
            line-height: 1.3;
        }

        .level-description {
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid;
            font-size: 13px;
        }

        textarea, input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
        }

        textarea:focus, input[type="text"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea {
            min-height: 80px;
        }

        textarea.large {
            min-height: 100px;
        }

        .form-divider {
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            margin-top: 24px;
        }

        .button-group {
            display: flex;
            gap: 12px;
        }

        button {
            padding: 12px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            flex: 1;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #2563eb;
        }

        .btn-primary:disabled {
            background-color: #d1d5db;
            cursor: not-allowed;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        .completed-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }

        .completed-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            border-bottom: 2px solid #e5e7eb;
        }

        th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }

        td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
        }

        .table-level {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .table-approved {
            color: #16a34a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .table-revision {
            color: #ea580c;
        }

        .hidden {
            display: none;
        }

        /* Creation Tools Styles */
        .creation-tools {
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .tools-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #111827;
        }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-button {
            padding: 10px 16px;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
            margin-bottom: -2px;
        }

        .tab-button:hover {
            color: #3b82f6;
        }

        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tool-section {
            margin-bottom: 20px;
        }

        .tool-section-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #374151;
        }

        .tool-group {
            margin-bottom: 16px;
        }

        .tool-group-label {
            font-size: 13px;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 8px;
            display: block;
        }

        .tool-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .tool-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #1f2937;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .tool-link:hover {
            background: #e5e7eb;
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .tool-link::before {
            content: "🔗";
            margin-right: 8px;
        }

        /* Accordion Styles */
        .accordion {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .accordion-item {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .accordion-header {
            padding: 12px 16px;
            background-color: #f3f4f6;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .accordion-header:hover {
            background-color: #e5e7eb;
        }

        .accordion-header.active {
            background-color: #3b82f6;
            color: white;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            padding: 0 16px;
        }

        .accordion-content.active {
            max-height: 1000px;
            padding: 12px 16px;
        }

        .arrow {
            font-size: 12px;
            transition: transform 0.3s;
        }

        /* Content Link Buttons */
        .tool-link-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tool-link-wrapper .tool-link {
            flex: 0 0 auto;
            min-width: 140px;
        }

        .content-links {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .content-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 500;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid;
            white-space: nowrap;
        }

        .content-link-btn.full-play {
            background-color: #dbeafe;
            border-color: #60a5fa;
            color: #1e40af;
        }

        .content-link-btn.full-play:hover {
            background-color: #bfdbfe;
            border-color: #3b82f6;
        }

        .content-link-btn.step-play {
            background-color: #fef3c7;
            border-color: #fbbf24;
            color: #92400e;
        }

        .content-link-btn.step-play:hover {
            background-color: #fde68a;
            border-color: #f59e0b;
        }

        /* Script Generator Info Box */
        .script-info-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .info-section {
            margin-bottom: 12px;
        }

        .info-section:last-child {
            margin-bottom: 0;
        }

        .info-title {
            font-weight: 700;
            font-size: 13px;
            color: #0c4a6e;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-item {
            font-size: 12px;
            color: #334155;
            padding: 4px 0;
            padding-left: 16px;
            position: relative;
        }

        .info-item:before {
            content: "•";
            position: absolute;
            left: 6px;
            color: #0ea5e9;
            font-weight: bold;
        }

        .info-highlight {
            background-color: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            color: #92400e;
            margin-top: 8px;
        }

        @media (max-width: 1400px) {
            .main-grid {
                grid-template-columns: 0.45fr 1.25fr 1fr;
            }
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .level-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .creation-tools {
                order: 3;
            }
        }

        /* ============================================
           🎧 Listening Test Player Styles
           ============================================ */

        /* 오디오 플레이어 영역 레이아웃 */
        #audioPlayers {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        #audioPlayers > div {
            flex: 1;
            min-width: 300px;
        }

        /* 단계별 재생 플레이어 */
        .listening-test-container {
            position: relative;
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 0;
            transition: all 0.3s ease;
            cursor: default;
        }

        .listening-test-container.minimized {
            height: 40px;
            cursor: pointer;
        }

        .listening-test-container.minimized .listening-header {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .listening-test-container.minimized .listening-body {
            display: none;
        }

        .listening-test-container.minimized .listening-progress-dots,
        .listening-test-container.minimized .speed-control-btn,
        .listening-test-container.minimized .subtitle-toggle-btn,
        .listening-test-container.minimized .replay-section-btn {
            display: none;
        }

        .listening-header {
            background: rgba(255,255,255,0.1);
            padding: 6px 10px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            cursor: move;
        }


        .listening-minimize-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transition: all 0.2s;
        }

        .listening-minimize-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .listening-body {
            padding: 10px;
        }

        .listening-text-display {
            background: rgba(255,255,255,0.95);
            border-left: 4px solid #4CAF50;
            padding: 8px;
            margin: 0 0 8px 0;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.5;
            min-height: 80px;
            max-height: 80px;
            overflow-y: auto;
            display: none;
            color: #333;
        }

        .listening-text-display.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .listening-audio-hidden {
            width: 100%;
            height: 40px;
            margin-bottom: 12px;
            border-radius: 8px;
            display: none;
        }

        body[data-audio-mode="full"] .listening-audio-hidden {
            display: block !important;
        }

        body[data-audio-mode="section"] .listening-audio-hidden {
            display: none !important;
        }

        /* Progress Dots */
        .listening-progress-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            position: relative;
            flex: 1;
        }

        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .progress-dot:hover {
            background: rgba(255,255,255,0.7);
            transform: scale(1.2);
        }

        .progress-dot.active {
            background: white;
            box-shadow: 0 0 10px rgba(255,255,255,0.8);
            transform: scale(1.3);
        }

        .progress-dot.completed {
            background: #4CAF50;
            box-shadow: 0 0 8px rgba(76,175,80,0.6);
        }

        /* Subtitle Toggle Button - mynote.php 동일 스타일 */
        .subtitle-toggle-btn {
            background: transparent;
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0;
        }

        .subtitle-toggle-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.08);
        }

        .subtitle-toggle-btn:active {
            transform: scale(0.96);
        }

        /* Replay Section Button - mynote.php 동일 스타일 */
        .replay-section-btn {
            background: transparent;
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0;
            opacity: 0.9;
        }

        .replay-section-btn:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.2);
            opacity: 1;
            box-shadow: 0 0 8px rgba(255,255,255,0.5);
        }

        .replay-section-btn:active {
            transform: scale(0.95);
        }

        /* Navigation Arrows */
        .nav-arrow {
            width: auto;
            height: auto;
            background: transparent;
            border: none;
            border-radius: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            transition: all 0.2s ease;
            padding: 0 4px;
        }

        .nav-arrow:hover:not(:disabled) {
            color: white;
            transform: scale(1.2);
        }

        .nav-arrow:disabled {
            color: rgba(255,255,255,0.3);
            cursor: not-allowed;
        }

        /* Speed Control Button */
        .speed-control-btn {
            background: transparent;
            border: none;
            color: white;
            padding: 5px 12px;
            border-radius: 14px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 50px;
            text-align: center;
        }

        .speed-control-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.08);
        }

        .speed-control-btn:active {
            transform: scale(0.96);
        }

        /* Subtitle Toggle Button */
        #subtitleToggleBtn {
            background: transparent;
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        #subtitleToggleBtn:hover {
            background: rgba(255,255,255,0.2) !important;
            transform: scale(1.08);
        }

        #subtitleToggleBtn:active {
            transform: scale(0.96) !important;
        }

        /* Subtitle Container */
        .subtitle-container {
            background: rgba(255,255,255,0.95);
            border-radius: 8px;
            padding: 10px;
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            max-height: 100px;
            overflow-y: auto;
            display: none;
        }

        .subtitle-container.visible {
            display: block;
        }

        /* Drilling Overlay */
        .drilling-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .drilling-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .drilling-overlay.active .drilling-overlay-content {
            transform: translateX(0);
        }

        .drilling-overlay-content {
            position: absolute;
            top: 0;
            right: 0;
            width: 33.333%;
            height: 100%;
            background: white;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: -4px 0 12px rgba(0, 0, 0, 0.15);
        }

        .drilling-overlay-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .drilling-overlay-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .drilling-close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .drilling-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .drilling-overlay-content iframe {
            flex: 1;
            width: 100%;
            height: calc(100% - 76px);
            border: none;
        }

        @media (max-width: 1200px) {
            .drilling-overlay-content {
                width: 50%;
            }
        }

        @media (max-width: 768px) {
            .drilling-overlay-content {
                width: 80%;
            }

            .drilling-overlay-header {
                padding: 15px 20px;
            }

            .drilling-overlay-header h3 {
                font-size: 16px;
            }

            .drilling-close-btn {
                width: 32px;
                height: 32px;
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .drilling-overlay-content {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="max-width">
            <div class="header">
                <h1>수학 콘텐츠 검수 시스템</h1>
                <p>교육 콘텐츠의 질을 체계적으로 평가하고 관리합니다 | 학생: <?php echo $stdname; ?></p>
            </div>

            <div class="main-grid">
                <!-- 좌측: 콘텐츠 목록 -->
                <div class="content-list">
                    <div class="list-title">검수 대상 콘텐츠</div>
                    <div class="content-items" id="contentList"></div>
                </div>

                <!-- 중앙: 검수 양식 -->
                <div class="review-form">
                    <div id="reviewEmpty" class="review-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p>검수할 콘텐츠를 선택해주세요</p>
                    </div>

                    <div id="reviewContent" class="hidden">
                        <div class="form-section">
                            <h2 id="selectedTitle" style="font-size: 18px; font-weight: 700; margin-bottom: 8px;"></h2>
                            <div class="form-meta">
                                <span id="selectedMeta"></span>
                            </div>
                            <div class="form-meta form-meta-teachers" id="selectedTeachers"></div>
                        </div>

                        <!-- 오디오 플레이어 -->
                        <div class="form-section" id="audioSection" style="display:none;">
                            <label class="form-label">오디오 재생</label>
                            <div id="audioPlayers"></div>
                        </div>

                        <!-- 콘텐츠 이미지 미리보기 -->
                        <div class="form-section" id="contentImageSection" style="display:none;">
                            <label class="form-label">콘텐츠 미리보기</label>
                            <div class="content-image-container">
                                <img id="contentImage" src="" alt="Content preview">
                            </div>
                        </div>

                        <div class="form-section">
                            <label class="form-label required">이 컨텐츠를 이해할 수 있는 학생의 레벨에 대한 예측</label>
                            <div class="level-grid" id="levelGrid"></div>
                        </div>

                        <div class="form-section">
                            <label class="form-label">종합 피드백</label>
                            <input type="text" id="feedback" placeholder="콘텐츠 검수에 대한 전반적인 의견을 작성해주세요" style="width:100%; padding:8px 12px; border:1px solid #e5e7eb; border-radius:6px; font-size:14px;">
                        </div>

                        <div class="form-section">
                            <label class="form-label">개선 필요사항 (콤마로 구분)</label>
                            <input type="text" id="improvements" placeholder="예: 예시 추가, 설명 명확화, 난이도 조정 등" style="width:100%; padding:8px 12px; border:1px solid #e5e7eb; border-radius:6px; font-size:14px;">
                        </div>

                        <div class="form-divider">
                            <div class="button-group">
                                <button class="btn-primary" id="submitBtn" onclick="submitReview()">
                                    ✓ 검수 완료
                                </button>
                                <button class="btn-secondary" onclick="cancelReview()">
                                    ✕ 취소
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 우측: 컨텐츠 제작 환경 -->
                <div class="creation-tools">
                    <div class="tools-title">컨텐츠 제작 환경</div>

                    <!-- Tab Navigation -->
                    <div class="tab-nav">
                        <button class="tab-button active" onclick="switchCreationTab('script-tab')">대본생성기</button>
                        <button class="tab-button" onclick="switchCreationTab('usage-tab')">사용법</button>
                    </div>

                    <!-- 대본생성기 탭 (아코디언 구조) -->
                    <div id="script-tab" class="tab-content active">
                        <!-- 안내 정보 박스 -->
                        <div class="script-info-box">
                            <div class="info-section">
                                <div class="info-title">🎵 TTS 생성 방식</div>
                                <div class="info-item"><strong>단일 TTS:</strong> 전체 내용을 한 번에 듣기 좋은 형태로 생성</div>
                                <div class="info-item"><strong>분할된 TTS:</strong> 단계별로 나누어서 학습 효과 극대화</div>
                                <div class="info-item"><strong>오개념 활용:</strong> 학생의 오개념을 활용한 맞춤형 설명</div>
                            </div>
                            <div class="info-section">
                                <div class="info-title">👤 적용 학생 페르소나</div>
                                <div class="info-item">현재 학생: <strong>✨이태상</strong></div>
                                <div class="info-highlight">
                                    💡 실제 담당학생을 대상으로 제작하는 경우 컨텐츠의 작용에 대한 정확도를 높일 수 있습니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion">
                            <!-- 개념 섹션 -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(event, 'concept')">
                                    📚 개념 학습 대본 생성
                                    <span class="arrow">▶</span>
                                </div>
                                <div id="concept" class="accordion-content">
                                    <div class="tool-section">
                                        <div class="tool-group">
                                            <span class="tool-group-label">🎯 표준컨텐츠 GPT</span>
                                            <div class="tool-links">
                                                <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci" target="_blank" class="tool-link">
                                                        대화식 설명
                                                    </a>
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn full-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-690210ae1d1c819191b6df2f183c2fa2" target="_blank" class="tool-link">
                                                       단계별 설명
                                                    </a>
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn step-play">
                                                       단계별 지시
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-6900359892f88191b5b5794ca62b7926" target="_blank" class="tool-link">
                                                        인지적 도제학습 TTS
                                                    </a>
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn step-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 유형 섹션 -->
                            <div class="accordion-item">
                                <div class="accordion-header active" onclick="toggleAccordion(event, 'type')">
                                    📝 유형 해설 대본 생성
                                    <span class="arrow">▼</span>
                                </div>
                                <div id="type" class="accordion-content active">
                                    <div class="tool-section">
                                        <div class="tool-group">
                                            <span class="tool-group-label">🎯 유형 대본 GPT</span>
                                            <div class="tool-links">
                                            <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-6908c183996c8191989d639daf35e33f-nareisyeon-saengseonggi-yuhyeong" target="_blank" class="tool-link">
                                                        대화식 설명
                                                     </a> 
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn full-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                               <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-6908c20dfe3c81919bf62a0eaef7b199-nareisyeon-saengseonggi-ogaenyeomhwalyong" target="_blank" class="tool-link">
                                                        오개념 대화
                                                    </a> 
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn full-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                             
                                         
                                                <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-6908c1750ac0819193e459705cc1a03b-injijeog-dojehagseub-yuhyeong" target="_blank" class="tool-link">
                                                        인지적 도제학습
                                                    </a>
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn step-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                              
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 지시 섹션 -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(event, 'instruction')">
                                    ✍️ 서술평가 지시사항 대본 생성
                                    <span class="arrow">▶</span>
                                </div>
                                <div id="instruction" class="accordion-content">
                                    <div class="tool-section">
                                        <div class="tool-group">
                                            <span class="tool-group-label">🎯 서술평가 GPT</span>
                                            <div class="tool-links">
                                                <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-6908c2198a4081919b7f8284e850061a-dangyebyeol-daehwasig-seosulpyeongga" target="_blank" class="tool-link">
                                                        단계별 서술평가
                                                    </a>
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn step-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 오답 섹션 -->
                            <div class="accordion-item">
                                <div class="accordion-header" onclick="toggleAccordion(event, 'wrong')">
                                    ❌ 오답 활용 대본 생성
                                    <span class="arrow">▶</span>
                                </div>
                                <div id="wrong" class="accordion-content">
                                    <div class="tool-section">
                                        <div class="tool-group">
                                            <span class="tool-group-label">🎯 평가준비 GPT</span>
                                            <div class="tool-links">
                                                <div class="tool-link-wrapper">
                                                    <a href="https://chatgpt.com/g/g-6908c21f52e0819193878ecfc848f4f9-injijeog-dojehagseub-pyeonggajunbi" target="_blank" class="tool-link">
                                                        평가준비
                                                    </a>
                                                    <div class="content-links">
                                                        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=1" target="_blank" class="content-link-btn step-play">
                                                            TTS 생성
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 대본 평가 GPT (아코디언 외부) -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                            <div class="tool-section">
                                <div class="tool-group">
                                    <span class="tool-group-label">⚖️ 대본 평가</span>
                                    <div class="tool-links">
                                        <div class="tool-link-wrapper">
                                            <a href="https://chatgpt.com/g/g-6908c2198a4081919b7f8284e850061a" target="_blank" class="tool-link" style="background-color: #f0fdf4; border-color: #22c55e; color: #166534;">
                                                대본 평가 GPT
                                            </a>
                                            <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                                                작성된 대본의 품질을 평가하고 개선 방향을 제시합니다.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 사용법 탭 -->
                    <div id="usage-tab" class="tab-content">
                        <div class="tool-section">
                            <div class="tool-section-title">📖 사용법 가이드</div>

                            <div style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151;">
                                    적용 학생 페르소나
                                </h4>
                                <p style="font-size: 12px; color: #6b7280; line-height: 1.6;">
                                    현재 학생: <strong style="color: #3b82f6;"><?php echo $stdname; ?></strong>
                                </p>
                            </div>

                            <div style="background: #f9fafb; padding: 12px; border-radius: 6px;">
                                <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151;">
                                    TTS 생성 방식
                                </h4>
                                <ul style="font-size: 12px; color: #6b7280; line-height: 1.8; padding-left: 20px;">
                                    <li><strong>단일 TTS:</strong> 전체 내용을 한 번에 듣기 좋은 형태로 생성</li>
                                    <li><strong>분할된 TTS:</strong> 단계별로 나누어서 학습 효과 극대화</li>
                                    <li><strong>오개념 활용:</strong> 학생의 오개념을 활용한 맞춤형 설명</li>
                                </ul>
                            </div>

                            <!-- 참고자료 카드 -->
                            <div style="background: #e0f2fe; padding: 12px; border-radius: 6px; margin-top: 12px; border-left: 4px solid #0ea5e9;">
                                <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #0c4a6e;">
                                    📚 참고자료
                                </h4>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="https://claude.ai/public/artifacts/a9a61aa3-566d-4c4d-9ddf-a5d2a807dd89?fullscreen=true" target="_blank" class="tool-link">
                                        개선전략 이해하기
                                    </a>
                                    <a href="https://claude.ai/public/artifacts/ded75a26-46ae-47fc-b5de-0f3792cc5fa9?fullscreen=true" target="_blank" class="tool-link">
                                        포도당 공급이 중요한 이유
                                    </a>
                                </div>
                            </div>

                            <!-- 이미지 교체 가이드 -->
                            <div style="margin-top: 16px;">
                                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #374151;">
                                    🖼️ 이미지 교체 도구
                                </h4>

                                <div style="background: #fef3c7; padding: 12px; border-radius: 6px; border-left: 4px solid #eab308; margin-bottom: 12px;">
                                    <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #92400e;">
                                        ⚠️ 이미지 교체 기준
                                    </h4>
                                    <p style="font-size: 12px; color: #6b7280; line-height: 1.8;">
                                        TTS를 아무리 개선해도 <strong style="color: #d97706;">해당 컨텐츠를 사용하는 표준레벨의 학생이 이해하는 것이 불가능해 보일 경우</strong> 이미지를 교체합니다.
                                    </p>
                                </div>

                                <div style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                    <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151;">
                                        📋 교체 판단 절차
                                    </h4>
                                    <ol style="font-size: 12px; color: #6b7280; line-height: 1.8; padding-left: 20px; margin: 0;">
                                        <li>표준 TTS, 단계별 TTS, 인지적 도제학습 TTS 등 모든 TTS 유형을 시도</li>
                                        <li>오개념 활용 평가 TTS로 학생의 이해도 확인</li>
                                        <li>위 모든 시도에도 불구하고 표준레벨 학생이 이해 불가능하다고 판단되면 이미지 교체 진행</li>
                                    </ol>
                                </div>

                                <div style="background: #e0e7ff; padding: 12px; border-radius: 6px;">
                                    <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #3730a3;">
                                        🔧 교체 도구 (준비중)
                                    </h4>
                                    <p style="font-size: 12px; color: #6b7280; line-height: 1.6; margin: 0;">
                                        이미지 교체 도구는 현재 준비 중입니다.<br>
                                        교체가 필요한 컨텐츠는 별도로 표시해 주시기 바랍니다.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 검수 완료 내역 -->
            <div id="completedSection" class="completed-section hidden">
                <div class="completed-title">검수 완료 내역</div>
                <table>
                    <thead>
                        <tr>
                            <th>콘텐츠</th>
                            <th>교사진</th>
                            <th>레벨</th>
                            <th>상태</th>
                            <th>검수일</th>
                        </tr>
                    </thead>
                    <tbody id="completedTable"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // PHP에서 전달받은 콘텐츠 데이터
        const contentsData = <?php echo $contentsJson; ?>;
        let contents = contentsData;

        // 레벨 설정
        const levelConfig = {
            'L1': { color: '#ef4444', bgColor: '#fee2e2', label: '심각한 결함이 있음', studentLevel: '하위권', icon: '🔴' },
            'L2': { color: '#f97316', bgColor: '#ffedd5', label: '학습효과가 우려됨', studentLevel: '중하위권', icon: '🟠' },
            'L3': { color: '#eab308', bgColor: '#fef3c7', label: '표준적인 학습과정을 가이드 함', studentLevel: '중위권', icon: '🟡' },
            'L4': { color: '#22c55e', bgColor: '#dcfce7', label: '중요한 부분들이 잘 처리됨', studentLevel: '중상위권', icon: '🟢' },
            'L5': { color: '#3b82f6', bgColor: '#dbeafe', label: '특별한 완성도에 도달', studentLevel: '상위권', icon: '🔵' }
        };

        let selectedContent = null;
        let selectedLevel = null;

        // 검수자 badge 렌더링 헬퍼 함수
        function renderReviewers(reviewers) {
            if (!reviewers || reviewers.length === 0) {
                return '';
            }
            return reviewers.map(r =>
                `<span class="reviewer-badge reviewer-badge-${r.level}">${r.name}</span>`
            ).join(' ');
        }

        // 콘텐츠 정렬 함수 (미검수 우선 → 낮은 레벨 우선)
        function sortContents() {
            contents.sort((a, b) => {
                // 미검수 여부 확인
                const aHasReview = a.reviewers && a.reviewers.length > 0;
                const bHasReview = b.reviewers && b.reviewers.length > 0;

                // 1순위: 미검수가 위로
                if (!aHasReview && bHasReview) return -1;
                if (aHasReview && !bHasReview) return 1;

                // 2순위: 둘 다 검수된 경우 → 낮은 레벨이 위로
                if (aHasReview && bHasReview) {
                    const levelOrder = { 'L1': 1, 'L2': 2, 'L3': 3, 'L4': 4, 'L5': 5 };
                    const aLevel = levelOrder[a.currentLevel] || 999;
                    const bLevel = levelOrder[b.currentLevel] || 999;

                    if (aLevel !== bLevel) {
                        return aLevel - bLevel; // 낮은 레벨이 먼저
                    }
                }

                // 3순위: 같은 상태면 페이지 번호 순서
                return a.pagenum - b.pagenum;
            });

            console.log('[Content Review] Contents sorted - unreviewed first, then low levels first');
        }

        // 초기화
        function init() {
            console.log('[Content Review] Initializing...');
            console.log('[Content Review] Total contents loaded:', contents.length);
            console.log('[Content Review] Contents data:', contents);

            if (contents.length === 0) {
                console.error('[Content Review Error] No contents loaded from database');
                alert('콘텐츠 데이터를 불러올 수 없습니다.\n\ncmid=' + <?php echo $cntid; ?> + '에 해당하는 콘텐츠가 없습니다.');
            }

            // 정렬 적용
            sortContents();

            renderContentList();
            renderLevelButtons();
        }

        // 학생 노트 열기
        function openStudentNote(noteUrl, event) {
            event.stopPropagation(); // 카드 클릭 이벤트 방지

            if (!noteUrl) {
                alert('❌ 학생 노트 URL이 없습니다.\n\n이 컨텐츠에 대한 노트가 아직 생성되지 않았습니다.');
                console.warn('[Content Review] No note URL available');
                return;
            }

            const fullUrl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?' + noteUrl;
            window.open(fullUrl, '_blank');

            console.log('[Content Review] Opening student note:', fullUrl);
        }

        // =============================================================================
        // Listening Test Player Functions
        // =============================================================================

        // 재생 속도 순환 함수 (1.0x → 1.25x → 1.5x → 1.75x → 2.0x → 1.0x) with localStorage
        let currentSpeedIndex = 0;
        const speedOptions = [1.0, 1.25, 1.5, 1.75, 2.0];

        // 페이지 로드 시 저장된 속도 복원
        function restorePlaybackSpeed(audioPlayer, speedBtn) {
            const savedSpeed = localStorage.getItem("contentsreview_playbackSpeed");

            if (!audioPlayer || !speedBtn) {
                console.error("[Speed Restore] File: contentsreview.php, Line: 1184, Elements not found");
                return;
            }

            if (savedSpeed) {
                const speed = parseFloat(savedSpeed);
                const speedIndex = speedOptions.indexOf(speed);

                if (speedIndex !== -1) {
                    currentSpeedIndex = speedIndex;
                    audioPlayer.playbackRate = speed;
                    speedBtn.textContent = speed.toFixed(2) + "x";
                    console.log("[Speed Restore] File: contentsreview.php, Line: 1194, Restored speed:", speed);
                }
            }
        }

        function cyclePlaybackSpeed(audioPlayerId, speedBtnId) {
            const audioPlayer = document.getElementById(audioPlayerId);
            const speedBtn = document.getElementById(speedBtnId);

            if (!audioPlayer || !speedBtn) {
                console.error("[Speed Control] File: contentsreview.php, Line: 1203, Elements not found");
                return;
            }

            // 다음 속도로 전환
            currentSpeedIndex = (currentSpeedIndex + 1) % speedOptions.length;
            const newSpeed = speedOptions[currentSpeedIndex];

            // 오디오 속도 적용
            audioPlayer.playbackRate = newSpeed;

            // 버튼 텍스트 업데이트
            speedBtn.textContent = newSpeed.toFixed(2) + "x";

            // localStorage에 저장
            localStorage.setItem("contentsreview_playbackSpeed", newSpeed.toString());

            console.log("[Speed Control] File: contentsreview.php, Line: 1221, Speed changed and saved:", newSpeed);
        }

        // 플레이어 최소화/최대화 토글
        function toggleListeningPlayer(containerId) {
            const container = document.getElementById(containerId);
            const minimizeBtn = container.querySelector('.listening-minimize-btn');
            const audioPlayer = container.querySelector('audio');

            if(container.classList.contains("minimized")) {
                // 펼침: 최소화 해제
                container.classList.remove("minimized");
                minimizeBtn.textContent = "−";

                // 자동 재생 (일시정지 상태인 경우 재생)
                if(audioPlayer && audioPlayer.paused) {
                    audioPlayer.play().catch(e => {
                        console.log("[Auto Resume] File: contentsreview.php, Line: 1245, Blocked:", e);
                    });
                    console.log("[Interface] File: contentsreview.php, Line: 1247, Player expanded - Auto resume audio");
                }
            } else {
                // 최소화: 자동 일시정지
                container.classList.add("minimized");
                minimizeBtn.textContent = "+";

                // 재생 중인 오디오 자동 일시정지
                if(audioPlayer && !audioPlayer.paused) {
                    audioPlayer.pause();
                    console.log("[Interface] File: contentsreview.php, Line: 1256, Player minimized - Auto pause audio");
                }
            }
        }

        // 자막 표시/숨기기 토글 함수 (구간별 + 전체재생 모두 지원)
        // 전역 자막 상태 변수 초기화 (localStorage에서 복원)
        if (typeof window.subtitlesVisible === "undefined") {
            const savedSubtitleState = localStorage.getItem("contentsreview_subtitlesVisible");
            window.subtitlesVisible = savedSubtitleState !== null ? (savedSubtitleState === "true") : false;
            console.log("[Settings] File: contentsreview.php, Line: 1267, Subtitle state restored from localStorage (default: closed):", window.subtitlesVisible);
        }

        window.toggleSubtitles = function(containerId) {
            // 자막 상태 토글
            window.subtitlesVisible = !window.subtitlesVisible;

            // localStorage에 저장
            localStorage.setItem("contentsreview_subtitlesVisible", window.subtitlesVisible);
            console.log("[Settings] File: contentsreview.php, Line: 1276, Subtitle state saved to localStorage:", window.subtitlesVisible);

            // 구간별 재생 자막 요소들
            const container = document.getElementById(containerId);
            const allSectionSubtitles = container.querySelectorAll(".listening-text-display");

            if (window.subtitlesVisible) {
                // 자막 표시 모드
                console.log("[Subtitle Toggle] File: contentsreview.php, Line: 1284, Action: Show All");

                // 현재 섹션의 자막 표시
                const currentSectionIndex = window.currentSection || 0;
                const subtitleToShow = allSectionSubtitles[currentSectionIndex];
                if (subtitleToShow) {
                    subtitleToShow.classList.add("active");
                }
            } else {
                // 자막 숨기기 모드
                console.log("[Subtitle Toggle] File: contentsreview.php, Line: 1294, Action: Hide All");

                // 모든 자막 숨기기
                allSectionSubtitles.forEach(function(subtitle) {
                    subtitle.classList.remove("active");
                });
            }
        };

        // 버튼 상태 업데이트 함수
        function updateButtonStates(prevBtn, nextBtn, currentSection, sectionCount) {
            prevBtn.disabled = (currentSection === 0);
            nextBtn.disabled = (currentSection >= sectionCount - 1);
            console.log("[Button States] File: contentsreview.php, Line: 1307, Prev:", prevBtn.disabled, "Next:", nextBtn.disabled);
        }

        // Progress dots 업데이트 함수
        function updateProgressDots(containerId, currentSection) {
            const container = document.getElementById(containerId);
            const dots = container.querySelectorAll(".progress-dot");

            dots.forEach((dot, index) => {
                dot.classList.remove("active", "completed");
                if(index < currentSection) {
                    dot.classList.add("completed");
                } else if(index === currentSection) {
                    dot.classList.add("active");
                }
            });
        }

        // 섹션 전환 함수
        function switchToSection(containerId, audioPlayerId, newSection, sectionFiles, sectionCount, textSections) {
            const container = document.getElementById(containerId);
            const audioPlayer = document.getElementById(audioPlayerId);
            const currentSection = parseInt(container.getAttribute('data-current-section') || '0');

            if(newSection < 0 || newSection >= sectionCount || newSection === currentSection) {
                return;
            }

            console.log("[Section Switch] File: contentsreview.php, Line: 1337, From:", currentSection, "To:", newSection);

            // 현재 텍스트 숨기기
            const allSubtitles = container.querySelectorAll(".listening-text-display");
            allSubtitles.forEach(subtitle => subtitle.classList.remove("active"));

            // 새 구간으로 이동
            container.setAttribute('data-current-section', newSection);
            window.currentSection = newSection;

            // 새 텍스트 표시 (자막 상태 확인)
            if(window.subtitlesVisible && allSubtitles[newSection]) {
                allSubtitles[newSection].classList.add("active");
                console.log("[Section Switch] File: contentsreview.php, Line: 1351, Subtitle shown for section:", newSection+1);
            } else if(allSubtitles[newSection]) {
                console.log("[Section Switch] File: contentsreview.php, Line: 1353, Subtitle hidden (user preference)");
            }

            // 오디오 정지
            audioPlayer.pause();
            audioPlayer.currentTime = 0;

            // 새 오디오 로드 및 재생
            audioPlayer.src = sectionFiles[newSection];
            audioPlayer.load();

            // 로드 완료 후 재생
            audioPlayer.addEventListener("loadeddata", function playNext() {
                // 저장된 속도 복원
                const savedSpeed = localStorage.getItem("contentsreview_playbackSpeed");
                if (savedSpeed) {
                    audioPlayer.playbackRate = parseFloat(savedSpeed);
                    console.log("[Section Switch] File: contentsreview.php, Line: 1374, Speed restored:", savedSpeed);
                }

                audioPlayer.play().catch(e => console.error("[Audio Error] File: contentsreview.php, Line: 1377, Error:", e));
                audioPlayer.removeEventListener("loadeddata", playNext);
            });

            // Progress dots 업데이트
            updateProgressDots(containerId, newSection);

            // 버튼 상태 업데이트
            const prevBtn = container.querySelector('.nav-arrow-prev');
            const nextBtn = container.querySelector('.nav-arrow-next');
            updateButtonStates(prevBtn, nextBtn, newSection, sectionCount);
        }

        // Listening test 인터페이스 초기화
        function initializeListeningTestInterface(containerId, audioPlayerId, sectionFiles, textSections, contentsId) {
            const container = document.getElementById(containerId);
            const audioPlayer = document.getElementById(audioPlayerId);
            const sectionCount = sectionFiles.length;

            container.setAttribute('data-current-section', '0');
            window.currentSection = 0;

            // 오디오 재생 종료 이벤트
            audioPlayer.addEventListener("ended", function() {
                const currentSection = parseInt(container.getAttribute('data-current-section'));
                const nextBtn = container.querySelector('.nav-arrow-next');
                const prevBtn = container.querySelector('.nav-arrow-prev');

                if(currentSection < sectionCount - 1) {
                    // 다음 구간이 있으면 다음 버튼 활성화
                    nextBtn.disabled = false;
                } else {
                    // 마지막 구간 완료
                    nextBtn.disabled = true;

                    // 모든 dots를 완료 상태로
                    const dots = container.querySelectorAll(".progress-dot");
                    dots.forEach(dot => {
                        dot.classList.remove("active");
                        dot.classList.add("completed");
                    });
                }

                // 이전 버튼 상태 업데이트
                prevBtn.disabled = (currentSection === 0);
            });

            // 이전 버튼 클릭
            const prevBtn = container.querySelector('.nav-arrow-prev');
            prevBtn.addEventListener("click", function() {
                const currentSection = parseInt(container.getAttribute('data-current-section'));
                console.log("[Prev Button] File: contentsreview.php, Line: 1435, Section:", currentSection);
                if(currentSection > 0) {
                    switchToSection(containerId, audioPlayerId, currentSection - 1, sectionFiles, sectionCount, textSections);
                }
            });

            // 다음 버튼 클릭
            const nextBtn = container.querySelector('.nav-arrow-next');
            nextBtn.addEventListener("click", function() {
                const currentSection = parseInt(container.getAttribute('data-current-section'));
                console.log("[Next Button] File: contentsreview.php, Line: 1444, Section:", currentSection);
                if(currentSection < sectionCount - 1) {
                    switchToSection(containerId, audioPlayerId, currentSection + 1, sectionFiles, sectionCount, textSections);
                }
            });

            // Progress dots 클릭 이벤트
            const progressDots = container.querySelectorAll(".progress-dot");
            progressDots.forEach((dot, index) => {
                dot.addEventListener("click", function() {
                    const currentSection = parseInt(container.getAttribute('data-current-section'));
                    // 현재 재생 중인 구간을 클릭한 경우: 다시 재생
                    if(index === currentSection) {
                        console.log("[Dot Click] File: contentsreview.php, Line: 1457, Replay current section:", currentSection);

                        // 현재 구간을 처음부터 다시 재생
                        if(audioPlayer) {
                            audioPlayer.currentTime = 0;

                            // 저장된 재생 속도 복원
                            const savedSpeed = localStorage.getItem("contentsreview_playbackSpeed");
                            if (savedSpeed) {
                                audioPlayer.playbackRate = parseFloat(savedSpeed);
                                console.log("[Dot Click] File: contentsreview.php, Line: 1467, Speed restored:", savedSpeed);
                            }

                            audioPlayer.play().catch(e => {
                                console.error("[Audio Error] File: contentsreview.php, Line: 1471, Replay error:", e);
                            });
                        }
                    } else {
                        // 다른 구간을 클릭한 경우: 해당 구간으로 이동
                        console.log("[Dot Click] File: contentsreview.php, Line: 1476, From:", currentSection, "To:", index);
                        switchToSection(containerId, audioPlayerId, index, sectionFiles, sectionCount, textSections);
                    }
                });
            });

            // 최소화/최대화 클릭 기능
            container.addEventListener("click", function(e) {
                const excludeElements = [
                    container.querySelector('.listening-minimize-btn'),
                    container.querySelector('.speed-control-btn'),
                    container.querySelector('.subtitle-toggle-btn'),
                    container.querySelector('.replay-section-btn'),
                    audioPlayer
                ];

                for(let el of excludeElements) {
                    if(el && (e.target === el || el.contains(e.target))) {
                        return;
                    }
                }

                if(container.classList.contains("minimized") && e.target === container) {
                    toggleListeningPlayer(containerId);
                }
            });

            // 속도 버튼
            const speedBtn = container.querySelector('.speed-control-btn');
            restorePlaybackSpeed(audioPlayer, speedBtn);

            // Replay 버튼 - 이 부분은 사용자가 drilling overlay를 원할 경우 활성화 가능
            const replayBtn = container.querySelector('.replay-section-btn');
            if(replayBtn) {
                replayBtn.addEventListener("click", function(e) {
                    e.stopPropagation();

                    const currentSection = parseInt(container.getAttribute('data-current-section'));
                    const sectionText = textSections[currentSection] || "";
                    const nstepValue = currentSection + 1;

                    console.log("[Replay Click] File: contentsreview.php, Line: 1519", {
                        contentsId: contentsId,
                        currentSection: currentSection,
                        nstep: nstepValue,
                        sectionText: sectionText.substring(0, 100)
                    });

                    // 상세보기 URL
                    const url = "https://mathking.kr/moodle/local/augmented_teacher/books/drillingmath.php?cid=" + contentsId +
                                "&ctype=1&nstep=" + nstepValue +
                                "&section=" + currentSection +
                                "&subtitle=" + encodeURIComponent(sectionText);

                    console.log("[Replay Click] File: contentsreview.php, Line: 1534, Opening drilling view:", url);

                    // 새 창으로 열기 (drilling overlay는 별도 페이지로)
                    window.open(url, '_blank');
                });
            }

            // 초기 버튼 상태 설정
            updateButtonStates(prevBtn, nextBtn, 0, sectionCount);

            console.log("[Listening Test] File: contentsreview.php, Line: 1546, Initialized with", sectionCount, "sections");
        }

        // Listening test HTML 생성 함수
        function generateListeningTestInterface(sectionData, contentsId) {
            const sectionFiles = sectionData.sections || [];
            const textSections = sectionData.text_sections || [];
            const sectionCount = sectionFiles.length;

            if (sectionCount === 0) {
                return '<p style="color:#dc2626;">❌ 리스닝 테스트 데이터가 없습니다.</p>';
            }

            const containerId = 'listeningContainer_' + contentsId;
            const audioPlayerId = 'audioPlayer_' + contentsId;
            const speedBtnId = 'speedBtn_' + contentsId;

            // Progress dots 생성
            const dotsHTML = Array.from({length: sectionCount}, (_, i) =>
                `<div class="progress-dot ${i === 0 ? 'active' : ''}"></div>`
            ).join('');

            // 자막 텍스트 div 생성
            const subtitlesHTML = textSections.map((text, i) =>
                `<div class="listening-text-display ${i === 0 && window.subtitlesVisible ? 'active' : ''}" id="listeningText_${contentsId}_${i+1}">
                    ${text}
                </div>`
            ).join('');

            const html = `
                <div id="${containerId}" class="listening-test-container" data-current-section="0">
                    <div class="listening-header">
                        <button class="listening-minimize-btn" onclick="toggleListeningPlayer('${containerId}')">−</button>
                        <div class="listening-progress-dots">
                            <button class="nav-arrow nav-arrow-prev">◀</button>
                            ${dotsHTML}
                            <button class="nav-arrow nav-arrow-next">▶</button>
                        </div>
                        <button class="speed-control-btn" id="${speedBtnId}" onclick="cyclePlaybackSpeed('${audioPlayerId}', '${speedBtnId}')">1.00x</button>
                        <button class="subtitle-toggle-btn" onclick="toggleSubtitles('${containerId}')" title="자막 보기/숨기기">📄</button>
                        <button class="replay-section-btn" title="상세보기">🔍</button>
                    </div>
                    <div class="listening-body">
                        <audio id="${audioPlayerId}" style="width:100%; height:30px;">
                            <source src="${sectionFiles[0]}" type="audio/mpeg">
                            Your browser does not support audio.
                        </audio>
                        ${subtitlesHTML}
                    </div>
                </div>
            `;

            // DOM에 추가된 후 초기화 (약간의 지연)
            setTimeout(() => {
                initializeListeningTestInterface(containerId, audioPlayerId, sectionFiles, textSections, contentsId);
            }, 100);

            return html;
        }

        // =============================================================================
        // End of Listening Test Player Functions
        // =============================================================================

        // 콘텐츠 목록 렌더링
        function renderContentList() {
            const contentList = document.getElementById('contentList');
            contentList.innerHTML = contents.map(content => `
                <div class="content-item ${selectedContent && selectedContent.id === content.id ? 'selected' : ''}" onclick="selectContent('${content.id}')">
                    <div class="content-item-header">
                        <div style="flex: 1;">
                            <div class="content-title" onclick="openStudentNote('${content.noteUrl}', event)" style="cursor:pointer; text-decoration:underline;">${content.title}</div>
                            <div class="content-meta">ID: ${content.id} · 페이지: ${content.pagenum}</div>
                            <div class="content-teachers">검토 : ${content.teachers}${content.reviewers && content.reviewers.length > 0 ? ' | ' + renderReviewers(content.reviewers) : ''}</div>
                        </div>
                        <span class="status-badge ${content.status === 'approved' ? 'status-approved' : 'status-pending'}">
                            ${content.status === 'approved' ? '승인됨' : '검수대기'}
                        </span>
                    </div>
                </div>
            `).join('');
        }

        // 레벨 버튼 렌더링
        function renderLevelButtons() {
            const levelGrid = document.getElementById('levelGrid');
            levelGrid.innerHTML = Object.entries(levelConfig).map(([key, config]) => `
                <button class="level-button ${selectedLevel === key ? 'selected' : ''}"
                        style="${selectedLevel === key ? `border: 2px solid ${config.color}; background-color: ${config.bgColor};` : `border-color: ${config.color};`} color: ${config.color};"
                        onclick="selectLevel('${key}')">
                    <div class="level-text">${key} ${config.studentLevel}</div>
                </button>
            `).join('');
        }

        // 콘텐츠 선택
        function selectContent(id) {
            console.log('[Content Review] Selecting content ID:', id);
            console.log('[Content Review] Available contents:', contents.map(c => c.id));

            selectedContent = contents.find(c => c.id === id);

            if (!selectedContent) {
                console.error('[Content Review Error] Content not found for ID:', id);
                alert('콘텐츠를 찾을 수 없습니다. ID: ' + id);
                return;
            }

            console.log('[Content Review] Selected content:', selectedContent);
            selectedLevel = null;

            document.getElementById('feedback').value = '';
            document.getElementById('improvements').value = '';

            renderContentList();
            renderLevelButtons();
            updateReviewForm();

            // Load existing review if available
            loadExistingReview(selectedContent.contentsid);
        }

        // 기존 검수 데이터 로드 (AJAX)
        function loadExistingReview(contentsid) {
            console.log('[Content Review] Loading existing review for contentsid:', contentsid);

            fetch('contentsreview_ajax.php?action=get_review&contentsid=' + contentsid)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.review) {
                    console.log('[Content Review] Existing review found:', data.review);

                    // Pre-populate form fields
                    selectedLevel = data.review.review_level;
                    document.getElementById('feedback').value = data.review.feedback || '';
                    document.getElementById('improvements').value = data.review.improvements || '';

                    // Update UI components
                    renderLevelButtons();
                    updateLevelDescription();
                    updateSubmitButton();

                    // Show info banner about existing review
                    const reviewDate = new Date(data.review.timecreated * 1000).toLocaleDateString('ko-KR', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const infoBanner = document.createElement('div');
                    infoBanner.id = 'existingReviewBanner';
                    infoBanner.style.cssText = 'background:#eff6ff; border:2px solid #3b82f6; padding:14px; border-radius:8px; margin-bottom:16px; font-size:13px;';
                    infoBanner.innerHTML = `
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <span style="font-size:18px;">ℹ️</span>
                            <strong style="color:#1e40af;">기존 검수 데이터 발견</strong>
                        </div>
                        <div style="color:#1e3a8a; line-height:1.6;">
                            <strong>레벨:</strong> ${data.review.review_level} (${levelConfig[data.review.review_level].label})<br>
                            <strong>검수자:</strong> ${data.review.reviewer_name} (${data.review.reviewer_role})<br>
                            <strong>검수일:</strong> ${reviewDate}<br>
                            <strong>버전:</strong> ${data.review.version}<br>
                            <strong>상태:</strong> ${data.review.review_status === 'approved' ? '✅ 승인됨' : '⏳ 대기중'}
                        </div>
                        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #93c5fd; color:#1e40af; font-size:12px;">
                            💡 수정하고 저장하면 새 버전(v${data.review.version + 1})으로 기록됩니다.
                        </div>
                    `;

                    // Remove existing banner if present
                    const oldBanner = document.getElementById('existingReviewBanner');
                    if (oldBanner) {
                        oldBanner.remove();
                    }

                    // Insert banner at top of review content
                    const reviewContent = document.getElementById('reviewContent');
                    const firstFormSection = reviewContent.querySelector('.form-section');
                    if (firstFormSection) {
                        reviewContent.insertBefore(infoBanner, firstFormSection);
                    } else {
                        reviewContent.insertBefore(infoBanner, reviewContent.firstChild);
                    }
                } else {
                    console.log('[Content Review] No existing review found');

                    // Remove existing review banner if present
                    const oldBanner = document.getElementById('existingReviewBanner');
                    if (oldBanner) {
                        oldBanner.remove();
                    }
                }
            })
            .catch(error => {
                console.error('[Content Review Error] Failed to load review:', error);
                // Non-critical error, don't show alert to user
            });
        }

        // 레벨 선택
        function selectLevel(level) {
            selectedLevel = level;
            renderLevelButtons();
            updateLevelDescription();
            updateSubmitButton();
        }

        // 레벨 설명 업데이트 (비활성화됨 - 버튼에 직접 표시)
        function updateLevelDescription() {
            // 레벨 정보는 이제 버튼에 직접 표시됨
        }

        // 검수 양식 업데이트
        function updateReviewForm() {
            const reviewEmpty = document.getElementById('reviewEmpty');
            const reviewContent = document.getElementById('reviewContent');

            if (selectedContent) {
                console.log('[Content Review] Updating review form for:', selectedContent);

                reviewEmpty.classList.add('hidden');
                reviewContent.classList.remove('hidden');

                document.getElementById('selectedTitle').textContent = selectedContent.title;
                document.getElementById('selectedMeta').textContent =
                    `ID: ${selectedContent.id} · 페이지: ${selectedContent.pagenum} · 주제: ${selectedContent.topic}`;
                document.getElementById('selectedTeachers').textContent = selectedContent.teachers;

                // 이미지 미리보기 업데이트
                const imageSection = document.getElementById('contentImageSection');
                const imageElement = document.getElementById('contentImage');

                if (selectedContent.imgSrc && selectedContent.imgSrc.trim() !== '') {
                    imageElement.src = selectedContent.imgSrc;
                    imageSection.style.display = 'block';
                    console.log('[Content Review] Displaying image:', selectedContent.imgSrc);
                } else {
                    imageSection.style.display = 'none';
                    console.log('[Content Review] No image available');
                }

                // 오디오 플레이어 업데이트
                const audioSection = document.getElementById('audioSection');
                const audioPlayers = document.getElementById('audioPlayers');
                let audioHTML = '';

                if (selectedContent.audiourl && selectedContent.audiourl.trim() !== '') {
                    audioHTML += `
                        <div style="margin-bottom:12px;">
                            <label style="font-size:13px; color:#6b7280; display:block; margin-bottom:4px;">
                                🎧 Full Narration (audiourl)
                            </label>
                            <audio controls style="width:100%; max-width:500px;">
                                <source src="${selectedContent.audiourl}" type="audio/wav">
                                <source src="${selectedContent.audiourl}" type="audio/mpeg">
                                Your browser does not support audio.
                            </audio>
                        </div>
                    `;
                    console.log('[Content Review] audiourl:', selectedContent.audiourl);
                }

                // 🎧 Procedural Memory (audiourl2) - Listening Test 지원
                if (selectedContent.audiourl2 && selectedContent.audiourl2.trim() !== '') {
                    // reflections1 필드 체크 (listening test 모드)
                    let isListeningTest = false;
                    let sectionData = null;

                    if (selectedContent.reflections1 && selectedContent.reflections1.trim() !== '') {
                        try {
                            const decoded = JSON.parse(selectedContent.reflections1);
                            if (decoded.mode === 'listening_test' && decoded.sections && decoded.sections.length > 0) {
                                isListeningTest = true;
                                sectionData = decoded;
                                console.log('[Content Review] Listening test mode detected:', decoded);
                            }
                        } catch (e) {
                            console.warn('[Content Review] File: contentsreview.php, Line: 1831, reflections1 parse error:', e);
                        }
                    }

                    if (isListeningTest && sectionData) {
                        // Listening Test 플레이어 사용
                        audioHTML += `
                            <div style="margin-bottom:12px;">
                                <label style="font-size:13px; color:#6b7280; display:block; margin-bottom:8px;">
                                    🎧 Procedural Memory - Listening Test (${sectionData.sections.length}개 구간)
                                </label>
                                ${generateListeningTestInterface(sectionData, selectedContent.contentsid)}
                            </div>
                        `;
                        console.log('[Content Review] Listening test player rendered with', sectionData.sections.length, 'sections');
                    } else {
                        // 기본 오디오 플레이어 사용
                        audioHTML += `
                            <div style="margin-bottom:12px;">
                                <label style="font-size:13px; color:#6b7280; display:block; margin-bottom:4px;">
                                    🎧 Procedural Memory (audiourl2)
                                </label>
                                <audio controls style="width:100%; max-width:500px;">
                                    <source src="${selectedContent.audiourl2}" type="audio/mp3">
                                    <source src="${selectedContent.audiourl2}" type="audio/mpeg">
                                    Your browser does not support audio.
                                </audio>
                            </div>
                        `;
                        console.log('[Content Review] Standard audio player for audiourl2:', selectedContent.audiourl2);
                    }
                }

                if (audioHTML !== '') {
                    audioPlayers.innerHTML = audioHTML;
                    audioSection.style.display = 'block';
                    console.log('[Content Review] Audio players rendered');
                } else {
                    audioSection.style.display = 'none';
                    console.log('[Content Review] No audio available');
                }

                updateLevelDescription();
                updateSubmitButton();
            } else {
                reviewEmpty.classList.remove('hidden');
                reviewContent.classList.add('hidden');
            }
        }

        // 제출 버튼 상태 업데이트
        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            if (selectedLevel) {
                submitBtn.disabled = false;
                submitBtn.style.backgroundColor = '#3b82f6';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.backgroundColor = '#d1d5db';
            }
        }

        // 검수 완료 (AJAX로 DB 저장)
        function submitReview() {
            if (!selectedLevel || !selectedContent) {
                console.warn('[Content Review] Cannot submit: no level or content selected');
                return;
            }

            console.log('[Content Review] Submitting review:', {
                content: selectedContent,
                level: selectedLevel
            });

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'submit_review');
            formData.append('contentsid', selectedContent.contentsid);
            formData.append('cmid', <?php echo $cntid; ?>);
            formData.append('pagenum', selectedContent.pagenum);
            formData.append('review_level', selectedLevel);
            formData.append('feedback', document.getElementById('feedback').value);
            formData.append('improvements', document.getElementById('improvements').value);
            formData.append('student_id', <?php echo $studentid; ?>);
            formData.append('wboard_id', selectedContent.wboardid || '');

            // Disable submit button during processing
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '💾 저장 중...';
            submitBtn.style.backgroundColor = '#9ca3af';

            // AJAX request to save review
            fetch('contentsreview_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ' ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('[Content Review] Server response:', data);

                if (data.success) {
                    // Update local content array with new reviewer information
                    const index = contents.findIndex(c => c.id === selectedContent.id);
                    const currentUser = '<?php echo $USER->firstname . " " . $USER->lastname; ?>';
                    const currentUserId = <?php echo $USER->id; ?>;

                    // reviewers 배열이 없으면 초기화
                    if (!contents[index].reviewers) {
                        contents[index].reviewers = [];
                    }

                    // 현재 사용자의 기존 검수 찾기
                    const existingReviewerIndex = contents[index].reviewers.findIndex(r => r.id === currentUserId);

                    if (existingReviewerIndex !== -1) {
                        // 기존 검수 업데이트 (같은 선생님이 다시 검수)
                        contents[index].reviewers[existingReviewerIndex].level = selectedLevel;
                        contents[index].reviewers[existingReviewerIndex].status = data.status;
                        contents[index].reviewers[existingReviewerIndex].date = new Date().toISOString().split('T')[0];
                    } else {
                        // 새 검수자 추가 (다른 선생님이 검수)
                        contents[index].reviewers.push({
                            id: currentUserId,
                            name: currentUser,
                            level: selectedLevel,
                            status: data.status,
                            date: new Date().toISOString().split('T')[0]
                        });
                    }

                    contents[index].status = data.status;
                    contents[index].currentLevel = selectedLevel;
                    contents[index].reviewDate = new Date().toISOString().split('T')[0];

                    // Show success message
                    alert('✅ ' + data.message + '\n\n콘텐츠: ' + selectedContent.title + '\n레벨: ' + selectedLevel + '\n평가: ' + levelConfig[selectedLevel].label + '\n버전: ' + data.version);

                    // Reset form
                    selectedContent = null;
                    selectedLevel = null;

                    // Update UI with re-sorting
                    sortContents(); // 재정렬 (미검수 우선 → 낮은 레벨 우선)
                    renderContentList();
                    renderLevelButtons();
                    updateReviewForm();
                    updateCompletedSection();
                } else {
                    // Show error message with details
                    let errorMsg = '❌ 저장 실패\n\n' + data.error;
                    if (data.file && data.line) {
                        errorMsg += '\n\n[파일: ' + data.file + ', 라인: ' + data.line + ']';
                    }
                    alert(errorMsg);
                    console.error('[Content Review Error] Server error:', data);
                }
            })
            .catch(error => {
                console.error('[Content Review Error] AJAX request failed:', error);
                alert('❌ 네트워크 오류가 발생했습니다.\n\n' + error.message + '\n\n서버 연결을 확인해주세요.');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                submitBtn.style.backgroundColor = '#3b82f6';
            });
        }

        // 취소
        function cancelReview() {
            selectedContent = null;
            selectedLevel = null;

            renderContentList();
            renderLevelButtons();
            updateReviewForm();
        }

        // 검수 완료 내역 업데이트
        function updateCompletedSection() {
            const completedContents = contents.filter(c => c.currentLevel);

            if (completedContents.length === 0) {
                document.getElementById('completedSection').classList.add('hidden');
                return;
            }

            document.getElementById('completedSection').classList.remove('hidden');

            const tbody = document.getElementById('completedTable');
            tbody.innerHTML = completedContents.map(c => `
                <tr>
                    <td>${c.title}</td>
                    <td style="color: #7c3aed; font-weight: 500;">${c.teachers}</td>
                    <td>
                        <span class="table-level" style="background-color: ${levelConfig[c.currentLevel].bgColor}; color: ${levelConfig[c.currentLevel].color};">
                            ${c.currentLevel}
                        </span>
                    </td>
                    <td>
                        ${c.status === 'approved'
                            ? '<span class="table-approved">✓ 승인</span>'
                            : '<span class="table-revision">수정요청</span>'}
                    </td>
                    <td style="color: #6b7280;">${c.reviewDate || '-'}</td>
                </tr>
            `).join('');
        }

        // =============================================================================
        // Creation Tools Tab Management
        // =============================================================================

        /**
         * 컨텐츠 제작 환경 탭 전환 함수
         * @param {string} tabId - 활성화할 탭의 ID (예: 'tts-tab', 'image-tab', 'usage-tab')
         */
        function switchCreationTab(tabId) {
            console.log('[Creation Tools] File: contentsreview.php, Line: switchCreationTab, Switching to tab:', tabId);

            // 모든 탭 버튼의 active 클래스 제거
            const tabButtons = document.querySelectorAll('.creation-tools .tab-button');
            tabButtons.forEach(btn => btn.classList.remove('active'));

            // 클릭된 탭 버튼에 active 클래스 추가
            const clickedButton = event ? event.target : null;
            if (clickedButton && clickedButton.classList.contains('tab-button')) {
                clickedButton.classList.add('active');
            }

            // 모든 탭 콘텐츠 숨김
            const tabContents = document.querySelectorAll('.creation-tools .tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // 선택된 탭 콘텐츠만 표시
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
                console.log('[Creation Tools] File: contentsreview.php, Line: switchCreationTab, Tab activated successfully:', tabId);
            } else {
                console.error('[Creation Tools Error] File: contentsreview.php, Line: switchCreationTab, Tab not found:', tabId);
            }
        }

        /**
         * 아코디언 섹션 토글 함수
         * @param {Event} event - 클릭 이벤트
         * @param {string} sectionId - 활성화할 섹션의 ID (예: 'concept', 'type', 'instruction', 'wrong')
         */
        function toggleAccordion(event, sectionId) {
            console.log('[Accordion] File: contentsreview.php, Line: toggleAccordion, Toggling section:', sectionId);

            try {
                // 모든 헤더와 콘텐츠에서 active 제거
                const allHeaders = document.querySelectorAll('.accordion-header');
                const allContents = document.querySelectorAll('.accordion-content');

                allHeaders.forEach(header => {
                    header.classList.remove('active');
                    const arrow = header.querySelector('.arrow');
                    if (arrow) arrow.textContent = '▶';
                });

                allContents.forEach(content => {
                    content.classList.remove('active');
                });

                // 클릭한 섹션만 활성화
                const targetContent = document.getElementById(sectionId);
                const targetHeader = event.currentTarget;

                if (targetContent && targetHeader) {
                    targetContent.classList.add('active');
                    targetHeader.classList.add('active');
                    const arrow = targetHeader.querySelector('.arrow');
                    if (arrow) arrow.textContent = '▼';
                    console.log('[Accordion] File: contentsreview.php, Line: toggleAccordion, Section activated successfully:', sectionId);
                } else {
                    console.error('[Accordion Error] File: contentsreview.php, Line: toggleAccordion, Section not found:', sectionId);
                }
            } catch (error) {
                console.error('[Accordion Error] File: contentsreview.php, Line: toggleAccordion, Error:', error);
            }
        }

        // 페이지 로드 시 초기화
        window.addEventListener('load', init);
    </script>
</body>
</html>
