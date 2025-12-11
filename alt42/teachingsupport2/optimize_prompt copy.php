<?php
/**
 * optimize_prompt.php - 프롬프트 관리 및 편집 페이지
 * 파일 위치: alt42/teachingsupport/optimize_prompt.php
 * 
 * 풀이 스타일과 힌트 종류에 적용될 프롬프트를 표시하고 편집할 수 있는 페이지
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// JSON 파일 경로
$promptsFile = __DIR__ . '/prompts/hint_prompts.json';

// 프롬프트 데이터 로드
$promptsData = [];
if (file_exists($promptsFile)) {
    $promptsData = json_decode(file_get_contents($promptsFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $promptsData = [];
        $loadError = 'JSON 파일 파싱 오류: ' . json_last_error_msg();
    }
} else {
    $loadError = '프롬프트 파일을 찾을 수 없습니다: ' . $promptsFile;
}

// 기본값 설정
$ttsGuidelines = $promptsData['ttsGuidelines'] ?? '';
$hintLevels = $promptsData['hintLevels'] ?? [];
$solutionStyles = $promptsData['solutionStyles'] ?? [];
$imageGuidelines = $promptsData['imageGuidelines'] ?? [];
$lastModified = $promptsData['lastModified'] ?? '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프롬프트 관리 - Optimize Prompt</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #3a3a5a;
        }
        
        .header h1 {
            font-size: 28px;
            color: #00d4ff;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header h1::before {
            content: '⚙️';
            font-size: 32px;
        }
        
        .header-info {
            text-align: right;
            font-size: 13px;
            color: #8a8a9a;
        }
        
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            background: #2a2a4a;
            padding: 8px;
            border-radius: 12px;
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #8a8a9a;
            cursor: pointer;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background: #3a3a5a;
            color: #fff;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: #fff;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section {
            background: #2a2a4a;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #3a3a5a;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #00d4ff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-desc {
            font-size: 13px;
            color: #8a8a9a;
            margin-bottom: 16px;
        }
        
        .prompt-card {
            background: #1a1a2e;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid #3a3a5a;
            transition: all 0.3s ease;
        }
        
        .prompt-card:hover {
            border-color: #00d4ff;
        }
        
        .prompt-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .prompt-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        
        .prompt-card-desc {
            font-size: 13px;
            color: #8a8a9a;
            margin-bottom: 12px;
        }
        
        .prompt-label {
            font-size: 12px;
            color: #00d4ff;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .prompt-textarea {
            width: 100%;
            min-height: 200px;
            background: #16213e;
            border: 1px solid #3a3a5a;
            border-radius: 8px;
            padding: 16px;
            color: #e0e0e0;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .prompt-textarea:focus {
            outline: none;
            border-color: #00d4ff;
        }
        
        .prompt-textarea.small {
            min-height: 100px;
        }
        
        .prompt-textarea.large {
            min-height: 350px;
        }
        
        .example-box {
            background: #16213e;
            border: 1px solid #3a5a3a;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }
        
        .example-label {
            font-size: 12px;
            color: #4ade80;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .example-text {
            font-size: 13px;
            color: #a0a0b0;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: #fff;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.4);
        }
        
        .btn-secondary {
            background: #3a3a5a;
            color: #e0e0e0;
        }
        
        .btn-secondary:hover {
            background: #4a4a6a;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff4757 0%, #cc3344 100%);
            color: #fff;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.4);
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 16px 24px;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toast.success {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
        }
        
        .toast.error {
            background: linear-gradient(135deg, #ff4757 0%, #cc3344 100%);
        }
        
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #3a3a5a;
            border-top: 4px solid #00d4ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .accordion {
            border: 1px solid #3a3a5a;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .accordion-item {
            border-bottom: 1px solid #3a3a5a;
        }
        
        .accordion-item:last-child {
            border-bottom: none;
        }
        
        .accordion-header {
            background: #1a1a2e;
            padding: 16px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }
        
        .accordion-header:hover {
            background: #2a2a4a;
        }
        
        .accordion-header.active {
            background: #2a2a4a;
            border-bottom: 1px solid #00d4ff;
        }
        
        .accordion-title {
            font-size: 15px;
            font-weight: 500;
            color: #fff;
        }
        
        .accordion-icon {
            font-size: 12px;
            color: #8a8a9a;
            transition: transform 0.3s ease;
        }
        
        .accordion-header.active .accordion-icon {
            transform: rotate(180deg);
        }
        
        .accordion-content {
            background: #16213e;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        
        .accordion-content.active {
            padding: 20px;
            max-height: 2000px;
        }
        
        .preview-box {
            background: #1a1a2e;
            border: 1px solid #3a5a6a;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        
        .preview-label {
            font-size: 12px;
            color: #00d4ff;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .preview-content {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            color: #a0a0b0;
            line-height: 1.5;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .char-count {
            font-size: 11px;
            color: #6a6a7a;
            text-align: right;
            margin-top: 4px;
        }
        
        .info-banner {
            background: linear-gradient(135deg, #3a5a6a 0%, #2a4a5a 100%);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-banner-icon {
            font-size: 24px;
        }
        
        .info-banner-text {
            font-size: 14px;
            color: #e0e0e0;
            line-height: 1.5;
        }
        
        .info-banner-text strong {
            color: #00d4ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>프롬프트 관리</h1>
            <div class="header-info">
                <?php if ($lastModified): ?>
                    마지막 수정: <?php echo date('Y-m-d H:i:s', strtotime($lastModified)); ?>
                <?php endif; ?>
                <br>
                사용자: <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?>
            </div>
        </div>
        
        <?php if (isset($loadError)): ?>
        <div class="info-banner" style="background: linear-gradient(135deg, #5a3a3a 0%, #4a2a2a 100%);">
            <div class="info-banner-icon">⚠️</div>
            <div class="info-banner-text">
                <strong>오류:</strong> <?php echo htmlspecialchars($loadError); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info-banner">
            <div class="info-banner-icon">💡</div>
            <div class="info-banner-text">
                이 페이지에서 <strong>힌트 종류</strong>와 <strong>풀이 스타일</strong>에 적용되는 프롬프트를 편집할 수 있습니다.
                수정된 프롬프트는 <strong>generate_dialog_narration.php</strong>에서 컨텐츠 정보와 결합되어 OpenAI API에 전달됩니다.
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('hints')">💡 힌트 종류</button>
            <button class="tab" onclick="showTab('styles')">🎨 풀이 스타일</button>
            <button class="tab" onclick="showTab('common')">📋 공통 설정</button>
            <button class="tab" onclick="showTab('preview')">👁️ 미리보기</button>
        </div>
        
        <!-- 힌트 종류 탭 -->
        <div id="tab-hints" class="tab-content active">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">💡 힌트 종류별 프롬프트</div>
                </div>
                <div class="section-desc">
                    학생이 힌트를 요청했을 때 사용되는 프롬프트입니다. 각 힌트 종류별로 다른 수준의 도움을 제공합니다.
                </div>
                
                <div class="accordion">
                    <?php foreach ($hintLevels as $key => $hint): ?>
                    <div class="accordion-item">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title"><?php echo htmlspecialchars($hint['name'] ?? $key); ?></div>
                            <div class="accordion-icon">▼</div>
                        </div>
                        <div class="accordion-content">
                            <div class="prompt-card-desc"><?php echo htmlspecialchars($hint['description'] ?? ''); ?></div>
                            
                            <div class="prompt-label">시스템 프롬프트</div>
                            <textarea class="prompt-textarea large" 
                                      id="hint-<?php echo $key; ?>-system"
                                      data-type="hint"
                                      data-key="<?php echo $key; ?>"
                                      data-field="systemPrompt"
                                      onkeyup="updateCharCount(this)"><?php echo htmlspecialchars($hint['systemPrompt'] ?? ''); ?></textarea>
                            <div class="char-count" id="count-hint-<?php echo $key; ?>-system">0자</div>
                            
                            <?php if (!empty($hint['example'])): ?>
                            <div class="example-box">
                                <div class="example-label">📝 출력 예시</div>
                                <textarea class="prompt-textarea small"
                                          id="hint-<?php echo $key; ?>-example"
                                          data-type="hint"
                                          data-key="<?php echo $key; ?>"
                                          data-field="example"><?php echo htmlspecialchars($hint['example']); ?></textarea>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- 풀이 스타일 탭 -->
        <div id="tab-styles" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">🎨 풀이 스타일별 프롬프트</div>
                </div>
                <div class="section-desc">
                    선생님이 풀이를 생성할 때 사용되는 스타일별 프롬프트입니다.
                </div>
                
                <div class="accordion">
                    <?php foreach ($solutionStyles as $key => $style): ?>
                    <div class="accordion-item">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title"><?php echo htmlspecialchars($style['name'] ?? $key); ?></div>
                            <div class="accordion-icon">▼</div>
                        </div>
                        <div class="accordion-content">
                            <div class="prompt-card-desc"><?php echo htmlspecialchars($style['description'] ?? ''); ?></div>
                            
                            <div class="prompt-label">시스템 프롬프트</div>
                            <textarea class="prompt-textarea large"
                                      id="style-<?php echo $key; ?>-system"
                                      data-type="style"
                                      data-key="<?php echo $key; ?>"
                                      data-field="systemPrompt"
                                      onkeyup="updateCharCount(this)"><?php echo htmlspecialchars($style['systemPrompt'] ?? ''); ?></textarea>
                            <div class="char-count" id="count-style-<?php echo $key; ?>-system">0자</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- 공통 설정 탭 -->
        <div id="tab-common" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">📋 공통 TTS 지침</div>
                </div>
                <div class="section-desc">
                    모든 힌트와 풀이에 공통으로 적용되는 TTS 변환 지침입니다.
                </div>
                
                <div class="prompt-label">TTS 지침 (모든 프롬프트에 추가됨)</div>
                <textarea class="prompt-textarea large"
                          id="ttsGuidelines"
                          data-type="common"
                          data-field="ttsGuidelines"
                          onkeyup="updateCharCount(this)"><?php echo htmlspecialchars($ttsGuidelines); ?></textarea>
                <div class="char-count" id="count-ttsGuidelines">0자</div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <div class="section-title">🖼️ 이미지 활용 지침</div>
                </div>
                
                <div class="prompt-label">힌트 모드 이미지 지침 (askhint)</div>
                <textarea class="prompt-textarea"
                          id="imageGuidelines-askhint"
                          data-type="imageGuidelines"
                          data-key="askhint"
                          onkeyup="updateCharCount(this)"><?php echo htmlspecialchars($imageGuidelines['askhint'] ?? ''); ?></textarea>
                <div class="char-count" id="count-imageGuidelines-askhint">0자</div>
                
                <div style="margin-top: 20px;"></div>
                
                <div class="prompt-label">일반 모드 이미지 지침 (normal)</div>
                <textarea class="prompt-textarea"
                          id="imageGuidelines-normal"
                          data-type="imageGuidelines"
                          data-key="normal"
                          onkeyup="updateCharCount(this)"><?php echo htmlspecialchars($imageGuidelines['normal'] ?? ''); ?></textarea>
                <div class="char-count" id="count-imageGuidelines-normal">0자</div>
            </div>
        </div>
        
        <!-- 미리보기 탭 -->
        <div id="tab-preview" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">👁️ 프롬프트 미리보기</div>
                </div>
                <div class="section-desc">
                    선택한 힌트 종류 또는 풀이 스타일의 최종 프롬프트를 미리볼 수 있습니다.
                </div>
                
                <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <div class="prompt-label">타입 선택</div>
                        <select id="preview-type" class="prompt-textarea" style="min-height: auto; padding: 10px;" onchange="updatePreview()">
                            <option value="">-- 선택 --</option>
                            <optgroup label="💡 힌트 종류">
                                <?php foreach ($hintLevels as $key => $hint): ?>
                                <option value="hint:<?php echo $key; ?>"><?php echo htmlspecialchars($hint['name'] ?? $key); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🎨 풀이 스타일">
                                <?php foreach ($solutionStyles as $key => $style): ?>
                                <option value="style:<?php echo $key; ?>"><?php echo htmlspecialchars($style['name'] ?? $key); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
                
                <div class="preview-box">
                    <div class="preview-label">📄 최종 시스템 프롬프트 (TTS 지침 포함)</div>
                    <div class="preview-content" id="preview-content">
                        타입을 선택하면 최종 프롬프트가 표시됩니다.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-primary" onclick="saveAllPrompts()">
                💾 모든 변경사항 저장
            </button>
            <button class="btn btn-secondary" onclick="location.reload()">
                🔄 새로고침
            </button>
            <button class="btn btn-danger" onclick="resetToDefault()">
                ⚠️ 기본값으로 초기화
            </button>
        </div>
    </div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <script>
        // 탭 전환
        function showTab(tabName) {
            // 모든 탭 비활성화
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // 선택한 탭 활성화
            document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }
        
        // 아코디언 토글
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const isActive = header.classList.contains('active');
            
            // 모든 아코디언 닫기
            // header.parentElement.parentElement.querySelectorAll('.accordion-header').forEach(h => h.classList.remove('active'));
            // header.parentElement.parentElement.querySelectorAll('.accordion-content').forEach(c => c.classList.remove('active'));
            
            // 클릭한 아코디언 토글
            if (!isActive) {
                header.classList.add('active');
                content.classList.add('active');
            } else {
                header.classList.remove('active');
                content.classList.remove('active');
            }
        }
        
        // 글자 수 업데이트
        function updateCharCount(textarea) {
            const id = textarea.id;
            const countEl = document.getElementById('count-' + id);
            if (countEl) {
                countEl.textContent = textarea.value.length.toLocaleString() + '자';
            }
        }
        
        // 페이지 로드 시 글자 수 초기화
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.prompt-textarea').forEach(textarea => {
                updateCharCount(textarea);
            });
        });
        
        // 미리보기 업데이트
        function updatePreview() {
            const select = document.getElementById('preview-type');
            const previewContent = document.getElementById('preview-content');
            
            if (!select.value) {
                previewContent.textContent = '타입을 선택하면 최종 프롬프트가 표시됩니다.';
                return;
            }
            
            const [type, key] = select.value.split(':');
            let systemPrompt = '';
            
            if (type === 'hint') {
                const textarea = document.getElementById(`hint-${key}-system`);
                if (textarea) {
                    systemPrompt = textarea.value;
                }
            } else if (type === 'style') {
                const textarea = document.getElementById(`style-${key}-system`);
                if (textarea) {
                    systemPrompt = textarea.value;
                }
            }
            
            // TTS 지침 추가
            const ttsGuidelines = document.getElementById('ttsGuidelines').value;
            if (ttsGuidelines) {
                systemPrompt += '\n\n' + ttsGuidelines;
            }
            
            previewContent.textContent = systemPrompt;
        }
        
        // 모든 프롬프트 저장
        async function saveAllPrompts() {
            showLoading(true);
            
            try {
                // 데이터 수집
                const data = {
                    ttsGuidelines: document.getElementById('ttsGuidelines').value,
                    hintLevels: {},
                    solutionStyles: {},
                    imageGuidelines: {}
                };
                
                // 힌트 레벨 수집
                <?php foreach ($hintLevels as $key => $hint): ?>
                data.hintLevels['<?php echo $key; ?>'] = {
                    name: <?php echo json_encode($hint['name'] ?? $key); ?>,
                    description: <?php echo json_encode($hint['description'] ?? ''); ?>,
                    systemPrompt: document.getElementById('hint-<?php echo $key; ?>-system')?.value || '',
                    example: document.getElementById('hint-<?php echo $key; ?>-example')?.value || ''
                };
                <?php endforeach; ?>
                
                // 풀이 스타일 수집
                <?php foreach ($solutionStyles as $key => $style): ?>
                data.solutionStyles['<?php echo $key; ?>'] = {
                    name: <?php echo json_encode($style['name'] ?? $key); ?>,
                    description: <?php echo json_encode($style['description'] ?? ''); ?>,
                    systemPrompt: document.getElementById('style-<?php echo $key; ?>-system')?.value || ''
                };
                <?php endforeach; ?>
                
                // 이미지 지침 수집
                data.imageGuidelines = {
                    askhint: document.getElementById('imageGuidelines-askhint')?.value || '',
                    normal: document.getElementById('imageGuidelines-normal')?.value || ''
                };
                
                // 저장 요청
                const response = await fetch('save_prompt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', '✅ 프롬프트가 저장되었습니다!');
                } else {
                    showToast('error', '❌ 저장 실패: ' + (result.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('Save error:', error);
                showToast('error', '❌ 저장 중 오류 발생: ' + error.message);
            } finally {
                showLoading(false);
            }
        }
        
        // 기본값으로 초기화
        async function resetToDefault() {
            if (!confirm('⚠️ 정말로 모든 프롬프트를 기본값으로 초기화하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.')) {
                return;
            }
            
            showLoading(true);
            
            try {
                const response = await fetch('save_prompt.php?action=reset', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', '✅ 기본값으로 초기화되었습니다!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', '❌ 초기화 실패: ' + (result.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('Reset error:', error);
                showToast('error', '❌ 초기화 중 오류 발생: ' + error.message);
            } finally {
                showLoading(false);
            }
        }
        
        // 로딩 표시
        function showLoading(show) {
            document.getElementById('loadingOverlay').classList.toggle('show', show);
        }
        
        // 토스트 메시지
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>

