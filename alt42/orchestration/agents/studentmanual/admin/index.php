<?php
/**
 * Student Manual System - Admin Page
 * File: alt42/orchestration/agents/studentmanual/admin/index.php
 *
 * 교사용 메뉴얼 관리 페이지
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Include error handler
require_once(__DIR__ . '/../includes/error_handler.php');

// 사용자 역할 확인 (교사만 접근 가능)
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? 'student';

if (!in_array($role, ['teacher', 'admin'])) {
    StudentManualErrorHandler::displayErrorPage(
        "접근 권한 없음",
        "교사만 이 페이지에 접근할 수 있습니다.",
        ['file' => __FILE__, 'line' => __LINE__]
    );
}

// 에이전트 목록
$agents = [
    'agent01' => '온보딩',
    'agent02' => '시험 일정',
    'agent03' => '목표 분석',
    'agent04' => '약점 분석',
    'agent05' => '학습 감정',
    'agent07' => '상호작용 타겟팅'
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>메뉴얼 관리 - 알파튜터42</title>
    <link rel="stylesheet" href="../assets/css/manual.css?v=<?php echo time(); ?>">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .admin-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            color: #1e293b;
            font-size: 32px;
            margin: 0;
        }

        .btn-primary {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 1024px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
        }

        .admin-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .admin-panel h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .items-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .item-row {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-info h3 {
            margin: 0 0 5px 0;
            color: #1e293b;
            font-size: 16px;
        }

        .item-info p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .upload-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .file-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: #f8fafc;
        }

        .file-upload-area.dragover {
            border-color: #667eea;
            background: #eff6ff;
        }

        .uploaded-files {
            margin-top: 20px;
        }

        .uploaded-file {
            padding: 10px;
            background: #f1f5f9;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- 헤더 -->
        <header class="admin-header">
            <h1>📚 메뉴얼 관리</h1>
            <div>
                <a href="../index.php" class="btn-primary">학생 페이지 보기</a>
                <button class="btn-primary" id="add-item-btn" style="margin-left: 10px;">새 항목 추가</button>
            </div>
        </header>

        <!-- 메인 컨텐츠 -->
        <div class="admin-content">
            <!-- 항목 목록 -->
            <div class="admin-panel">
                <h2>메뉴얼 항목 목록</h2>
                <div class="items-list" id="items-list">
                    <p>로딩 중...</p>
                </div>
            </div>

            <!-- 항목 추가/수정 폼 -->
            <div class="admin-panel">
                <h2 id="form-title">새 메뉴얼 항목 추가</h2>
                <form id="item-form">
                    <input type="hidden" id="item-id" name="id" value="">
                    
                    <div class="form-group">
                        <label for="title">제목 *</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="agent_id">에이전트 *</label>
                        <select id="agent_id" name="agent_id" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($agents as $agentId => $agentName): ?>
                                <option value="<?php echo htmlspecialchars($agentId); ?>">
                                    <?php echo htmlspecialchars($agentName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">설명</label>
                        <textarea id="description" name="description"></textarea>
                    </div>

                    <!-- 컨텐츠 업로드 섹션 -->
                    <div class="upload-section">
                        <h3>컨텐츠 추가</h3>
                        
                        <div class="form-group">
                            <label for="content_type">컨텐츠 타입</label>
                            <select id="content_type" name="content_type">
                                <option value="image">이미지</option>
                                <option value="video">동영상</option>
                                <option value="audio">음성</option>
                                <option value="link">외부 링크</option>
                            </select>
                        </div>

                        <div id="file-upload-area" class="file-upload-area">
                            <p>파일을 드래그하거나 클릭하여 업로드</p>
                            <input type="file" id="file-input" style="display: none;" accept="image/*,video/*,audio/*">
                        </div>

                        <div id="link-input-group" class="form-group hidden">
                            <label for="external_url">외부 링크 URL</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="url" id="external_url" name="external_url" placeholder="https://..." style="flex: 1;">
                                <button type="button" class="btn-primary" id="add-link-btn" style="white-space: nowrap;">추가</button>
                            </div>
                        </div>

                        <div class="uploaded-files" id="uploaded-files"></div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn-primary">저장</button>
                        <button type="button" class="btn-primary" id="cancel-btn" style="background: #64748b;">취소</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.agents = <?php echo json_encode($agents, JSON_UNESCAPED_UNICODE); ?>;
        window.apiBase = '../api/';
    </script>
    <script src="../assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>

