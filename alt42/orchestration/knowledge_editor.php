<?php
/**
 * Knowledge File Editor Interface
 * File: knowledge_editor.php:1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$filename = $_GET['file'] ?? '의사결정 지식.md';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>지식 파일 편집 - <?php echo htmlspecialchars($filename); ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f3f4f6;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .header {
      background: white;
      padding: 16px 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header h1 {
      font-size: 18px;
      color: #111827;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header .filename {
      color: #6366f1;
      font-weight: 700;
    }

    .header .actions {
      display: flex;
      gap: 8px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      border: none;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background: #3b82f6;
      color: white;
    }

    .btn-primary:hover {
      background: #2563eb;
    }

    .btn-secondary {
      background: #e5e7eb;
      color: #374151;
    }

    .btn-secondary:hover {
      background: #d1d5db;
    }

    .editor-container {
      flex: 1;
      display: flex;
      overflow: hidden;
    }

    .editor-pane {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: white;
      margin: 16px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .pane-header {
      padding: 12px 16px;
      background: #f9fafb;
      border-bottom: 1px solid #e5e7eb;
      font-weight: 600;
      color: #374151;
      font-size: 14px;
    }

    .pane-content {
      flex: 1;
      overflow: auto;
    }

    #editor {
      width: 100%;
      height: 100%;
      padding: 16px;
      border: none;
      font-family: 'Courier New', monospace;
      font-size: 14px;
      line-height: 1.6;
      resize: none;
      outline: none;
    }

    #preview {
      padding: 16px;
      line-height: 1.6;
      color: #374151;
    }

    #preview h1 { font-size: 24px; margin: 16px 0 8px; color: #111827; }
    #preview h2 { font-size: 20px; margin: 16px 0 8px; color: #111827; }
    #preview h3 { font-size: 18px; margin: 16px 0 8px; color: #111827; }
    #preview p { margin: 8px 0; }
    #preview ul, #preview ol { margin: 8px 0; padding-left: 24px; }
    #preview li { margin: 4px 0; }
    #preview code {
      background: #f3f4f6;
      padding: 2px 6px;
      border-radius: 3px;
      font-family: 'Courier New', monospace;
      font-size: 13px;
    }
    #preview pre {
      background: #1f2937;
      color: #f9fafb;
      padding: 12px;
      border-radius: 6px;
      overflow-x: auto;
      margin: 8px 0;
    }
    #preview pre code {
      background: none;
      color: inherit;
      padding: 0;
    }

    .status-bar {
      background: white;
      padding: 8px 24px;
      border-top: 1px solid #e5e7eb;
      font-size: 13px;
      color: #6b7280;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .status-message {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .status-message.success {
      color: #059669;
    }

    .status-message.error {
      color: #dc2626;
    }

    .loading {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid #e5e7eb;
      border-top-color: #3b82f6;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

  <div class="header">
    <h1>
      📝 <span class="filename" id="filename-display"><?php echo htmlspecialchars($filename); ?></span>
    </h1>
    <div class="actions">
      <button class="btn btn-secondary" onclick="window.close()">닫기</button>
      <button class="btn btn-primary" onclick="saveFile()">💾 저장</button>
    </div>
  </div>

  <div class="editor-container">
    <div class="editor-pane">
      <div class="pane-header">📝 편집</div>
      <div class="pane-content">
        <textarea id="editor" placeholder="마크다운 내용을 입력하세요..."></textarea>
      </div>
    </div>

    <div class="editor-pane">
      <div class="pane-header">👁️ 미리보기</div>
      <div class="pane-content">
        <div id="preview"></div>
      </div>
    </div>
  </div>

  <div class="status-bar">
    <div class="status-message" id="status-message">
      준비됨
    </div>
    <div id="char-count">0 자</div>
  </div>

  <script>
    const filename = <?php echo json_encode($filename); ?>;
    const editor = document.getElementById('editor');
    const preview = document.getElementById('preview');
    const statusMessage = document.getElementById('status-message');
    const charCount = document.getElementById('char-count');

    // Load file content
    async function loadFile() {
      try {
        setStatus('loading', '파일 로딩 중...');

        const response = await fetch('api/knowledge_editor.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'read',
            filename: filename
          })
        });

        const data = await response.json();

        if (!data.success) {
          throw new Error(data.error || 'Failed to load file. File: knowledge_editor.php:234');
        }

        editor.value = data.data.content;
        updatePreview();
        setStatus('success', '파일 로드 완료');

      } catch (error) {
        console.error('Load error:', error);
        setStatus('error', '파일 로드 실패: ' + error.message);
      }
    }

    // Save file content
    async function saveFile() {
      try {
        setStatus('loading', '저장 중...');

        const response = await fetch('api/knowledge_editor.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'write',
            filename: filename,
            content: editor.value
          })
        });

        const data = await response.json();

        if (!data.success) {
          throw new Error(data.error || 'Failed to save file. File: knowledge_editor.php:268');
        }

        setStatus('success', '저장 완료 (' + data.data.timestamp + ')');

      } catch (error) {
        console.error('Save error:', error);
        setStatus('error', '저장 실패: ' + error.message);
      }
    }

    // Update preview with markdown rendering
    function updatePreview() {
      const text = editor.value;

      // Simple markdown to HTML conversion
      let html = text
        // Headers
        .replace(/^### (.*$)/gim, '<h3>$1</h3>')
        .replace(/^## (.*$)/gim, '<h2>$1</h2>')
        .replace(/^# (.*$)/gim, '<h1>$1</h1>')
        // Bold
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        // Italic
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        // Code blocks
        .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
        // Inline code
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        // Lists
        .replace(/^\- (.*$)/gim, '<li>$1</li>')
        .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
        // Line breaks
        .replace(/\n\n/g, '</p><p>')
        .replace(/\n/g, '<br>');

      // Wrap in paragraphs
      if (!html.startsWith('<h') && !html.startsWith('<ul')) {
        html = '<p>' + html + '</p>';
      }

      preview.innerHTML = html;

      // Update character count
      charCount.textContent = text.length + ' 자';
    }

    // Set status message
    function setStatus(type, message) {
      statusMessage.className = 'status-message ' + type;
      if (type === 'loading') {
        statusMessage.innerHTML = '<span class="loading"></span> ' + message;
      } else {
        statusMessage.textContent = message;
      }
    }

    // Auto-save on Ctrl+S
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveFile();
      }
    });

    // Update preview on input
    editor.addEventListener('input', updatePreview);

    // Load file on page load
    loadFile();
  </script>

</body>
</html>
